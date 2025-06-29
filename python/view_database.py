#!/usr/bin/env python3
"""
View Database for WP Fukami Lens AI

This script provides paginated viewing of LanceDB database contents with search and filtering.
"""

import sys
import json
import os
from datetime import datetime, timedelta
from typing import List, Dict, Any, Optional

# Set environment variables for HuggingFace cache
os.environ["HF_HOME"] = "/tmp"
os.environ["HF_HUB_CACHE"] = "/tmp/huggingface"
os.environ["XDG_CACHE_HOME"] = "/tmp"


class NumpyEncoder(json.JSONEncoder):
    """Custom JSON encoder to handle NumPy types"""
    def default(self, obj):
        if hasattr(obj, 'tolist'):  # Handle numpy arrays and similar
            return obj.tolist()
        elif hasattr(obj, '__iter__') and not isinstance(obj, (str, bytes, dict)):
            return list(obj)
        return super(NumpyEncoder, self).default(obj)


try:
    import lancedb
    import pandas as pd
    import numpy as np
except ImportError as e:
    print(json.dumps({
        'success': False,
        'data': f'Missing required packages: {str(e)}. Please install lancedb and pandas.'
    }, cls=NumpyEncoder))
    sys.exit(1)


class DatabaseViewer:
    """View LanceDB database contents with pagination and filtering"""
    
    def __init__(self, db_path: str, table_name: str = 'wordpress_posts'):
        self.db_path = db_path
        self.table_name = table_name
        self.db = lancedb.connect(db_path)
        
    def get_paginated_data(self, page: int = 1, per_page: int = 20, 
                          search: str = '', date_filter: str = '') -> Dict[str, Any]:
        """Get paginated data with optional search and filtering"""
        try:
            if self.table_name not in self.db.table_names():
                return {
                    'success': False,
                    'data': 'No database table found'
                }
            
            table = self.db.open_table(self.table_name)
            
            # Build query
            query = table.search()
            
            # Apply search filter
            if search:
                search_terms = search.lower().split()
                search_conditions = []
                for term in search_terms:
                    search_conditions.append(f"LOWER(title) LIKE '%{term}%' OR LOWER(content) LIKE '%{term}%'")
                if search_conditions:
                    search_query = " OR ".join(search_conditions)
                    query = query.where(f"({search_query})")
            
            # Apply date filter
            if date_filter:
                today = datetime.now().date()
                if date_filter == 'today':
                    start_date = today
                    end_date = today
                elif date_filter == 'week':
                    start_date = today - timedelta(days=today.weekday())
                    end_date = today
                elif date_filter == 'month':
                    start_date = today.replace(day=1)
                    end_date = today
                elif date_filter == 'year':
                    start_date = today.replace(month=1, day=1)
                    end_date = today
                else:
                    start_date = None
                    end_date = None
                
                if start_date and end_date:
                    query = query.where(f"date >= '{start_date}' AND date <= '{end_date}'")
            
            # Get total count for pagination
            all_results = query.to_pandas()
            total_count = len(all_results)
            
            # Apply pagination
            start_idx = (page - 1) * per_page
            end_idx = start_idx + per_page
            
            if total_count > 0:
                paginated_results = all_results.iloc[start_idx:end_idx]
            else:
                paginated_results = pd.DataFrame()
            
            # Convert to list of dictionaries
            posts = []
            for _, row in paginated_results.iterrows():
                # Handle embedding conversion properly
                embedding = None
                if 'embedding' in row and row['embedding'] is not None:
                    if hasattr(row['embedding'], 'tolist'):
                        embedding = row['embedding'].tolist()
                    elif hasattr(row['embedding'], '__iter__'):
                        embedding = list(row['embedding'])
                    else:
                        embedding = row['embedding']
                
                # Handle categories and tags conversion
                categories = []
                if 'categories' in row and row['categories'] is not None:
                    if hasattr(row['categories'], 'tolist'):
                        categories = row['categories'].tolist()
                    elif hasattr(row['categories'], '__iter__'):
                        categories = list(row['categories'])
                    else:
                        categories = row['categories']
                
                tags = []
                if 'tags' in row and row['tags'] is not None:
                    if hasattr(row['tags'], 'tolist'):
                        tags = row['tags'].tolist()
                    elif hasattr(row['tags'], '__iter__'):
                        tags = list(row['tags'])
                    else:
                        tags = row['tags']
                
                post = {
                    'id': int(row['id']),
                    'title': str(row['title']),
                    'content': str(row['content']),
                    'date': str(row['date']),
                    'permalink': str(row['permalink']),
                    'categories': categories,
                    'tags': tags,
                    'embedding': embedding
                }
                posts.append(post)
            
            return {
                'success': True,
                'data': {
                    'posts': posts,
                    'total_count': total_count,
                    'page': page,
                    'per_page': per_page,
                    'total_pages': (total_count + per_page - 1) // per_page
                }
            }
            
        except Exception as e:
            return {
                'success': False,
                'data': f'Error retrieving data: {str(e)}'
            }
    
    def get_sample_data(self, count: int = 20) -> Dict[str, Any]:
        """Generate sample data for testing if database is empty"""
        try:
            sample_posts = []
            
            for i in range(1, count + 1):
                post = {
                    'id': i,
                    'title': f'Sample Post {i}',
                    'content': f'This is sample content for post {i}. It contains some text that can be used for testing the database viewer functionality.',
                    'date': (datetime.now() - timedelta(days=i)).strftime('%Y-%m-%d'),
                    'permalink': f'https://example.com/sample-post-{i}',
                    'categories': ['Sample', 'Test'],
                    'tags': ['sample', 'test', f'post-{i}'],
                    'embedding': [0.1 * i + 0.01 * j for j in range(1536)]  # Sample embedding
                }
                sample_posts.append(post)
            
            return {
                'success': True,
                'data': {
                    'posts': sample_posts,
                    'total_count': count,
                    'page': 1,
                    'per_page': count,
                    'total_pages': 1
                }
            }
            
        except Exception as e:
            return {
                'success': False,
                'data': f'Error generating sample data: {str(e)}'
            }


def main():
    """Main function to handle database viewing"""
    if len(sys.argv) < 2:
        print(json.dumps({
            'success': False,
            'data': 'Usage: python view_database.py <input_file>'
        }, cls=NumpyEncoder))
        sys.exit(1)
    
    input_file = sys.argv[1]
    
    try:
        # Read input data
        with open(input_file, 'r', encoding='utf-8') as f:
            data = json.load(f)
        
        # Initialize database viewer
        db_path = data.get('db_path', '/tmp/lancedb')
        table_name = data.get('table_name', 'wordpress_posts')
        viewer = DatabaseViewer(db_path, table_name)
        
        # Get parameters
        page = data.get('page', 1)
        per_page = data.get('per_page', 20)
        search = data.get('search', '')
        date_filter = data.get('date_filter', '')
        
        # Check if database exists and has data
        if table_name not in viewer.db.table_names():
            # Return sample data for testing
            result = viewer.get_sample_data(per_page)
        else:
            # Get real data
            result = viewer.get_paginated_data(page, per_page, search, date_filter)
        
        # Output result
        print(json.dumps(result, ensure_ascii=False, cls=NumpyEncoder))
        
    except Exception as e:
        print(json.dumps({
            'success': False,
            'data': f'Error: {str(e)}'
        }, cls=NumpyEncoder))


if __name__ == '__main__':
    main() 