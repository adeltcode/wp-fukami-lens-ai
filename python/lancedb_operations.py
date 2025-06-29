#!/usr/bin/env python3
"""
LanceDB Operations for WP Fukami Lens AI

This script handles LanceDB vector database operations including:
- Storing post embeddings
- Searching for similar content
- Database statistics
"""

import sys
import json
import os
import tempfile
from datetime import datetime
from typing import List, Dict, Any, Optional

# Set environment variables for HuggingFace cache
os.environ["HF_HOME"] = "/tmp"
os.environ["HF_HUB_CACHE"] = "/tmp/huggingface"
os.environ["XDG_CACHE_HOME"] = "/tmp"

try:
    import lancedb
    import numpy as np
    import pandas as pd
    import pyarrow as pa
    from pydantic import BaseModel, Field
except ImportError as e:
    print(json.dumps({
        'success': False,
        'data': f'Missing required packages: {str(e)}. Please install lancedb, numpy, pandas, pyarrow, and pydantic.'
    }))
    sys.exit(1)


class PostData(BaseModel):
    """Pydantic model for WordPress post data"""
    id: int
    title: str
    content: str
    date: str
    permalink: str
    categories: List[str] = Field(default_factory=list)
    tags: List[str] = Field(default_factory=list)


class LanceDBManager:
    """Manages LanceDB operations for WordPress posts"""
    
    def __init__(self, db_path: str, table_name: str = 'wordpress_posts'):
        self.db_path = db_path
        self.table_name = table_name
        self.db = lancedb.connect(db_path)
        
    def create_table_if_not_exists(self):
        """Create the posts table if it doesn't exist"""
        if self.table_name not in self.db.table_names():
            # Define schema using PyArrow with microsecond precision for timestamps
            schema = pa.schema([
                ('id', pa.int64()),
                ('title', pa.string()),
                ('content', pa.string()),
                ('date', pa.string()),
                ('permalink', pa.string()),
                ('categories', pa.list_(pa.string())),
                ('tags', pa.list_(pa.string())),
                ('embedding', pa.list_(pa.float32(), 1536)),  # OpenAI text-embedding-3-small dimension
                ('created_at', pa.timestamp('us'))  # Use microsecond precision to match existing data
            ])
            
            # Create empty table with schema
            self.db.create_table(self.table_name, schema=schema)
            print(f"Created table '{self.table_name}' in LanceDB")
    
    def store_embeddings(self, posts: List[Dict], embeddings: List[List[float]]) -> Dict[str, Any]:
        """Store post embeddings in LanceDB"""
        try:
            self.create_table_if_not_exists()
            table = self.db.open_table(self.table_name)
            
            # Prepare data for insertion
            data = []
            for post, embedding in zip(posts, embeddings):
                data.append({
                    'id': post['id'],
                    'title': post['title'],
                    'content': post['content'],
                    'date': post['date'],
                    'permalink': post['permalink'],
                    'categories': post.get('categories', []),
                    'tags': post.get('tags', []),
                    'embedding': embedding,
                    'created_at': datetime.now().replace(microsecond=datetime.now().microsecond)  # Ensure microsecond precision
                })
            
            # Insert data (LanceDB will handle duplicates automatically)
            table.add(data)
            
            return {
                'success': True,
                'data': f'Stored {len(data)} embeddings in LanceDB'
            }
            
        except Exception as e:
            return {
                'success': False,
                'data': f'Failed to store embeddings: {str(e)}'
            }
    
    def search_similar(self, query_embedding: List[float], limit: int = 5, 
                      filters: Optional[Dict] = None) -> Dict[str, Any]:
        """Search for similar content using embeddings"""
        try:
            if self.table_name not in self.db.table_names():
                return {
                    'success': False,
                    'data': 'No embeddings table found. Please store embeddings first.'
                }
            
            table = self.db.open_table(self.table_name)
            
            # Build query
            query = table.search(query_embedding).limit(limit)
            
            # Apply filters if provided
            if filters:
                if 'start_date' in filters:
                    query = query.where(f"date >= '{filters['start_date']}'")
                if 'end_date' in filters:
                    query = query.where(f"date <= '{filters['end_date']}'")
                if 'categories' in filters and filters['categories']:
                    categories_filter = " OR ".join([f"'{cat}' IN categories" for cat in filters['categories']])
                    query = query.where(f"({categories_filter})")
            
            # Execute search
            results = query.to_pandas()
            
            # Convert results to list of dictionaries
            similar_posts = []
            for _, row in results.iterrows():
                similar_posts.append({
                    'id': int(row['id']),
                    'title': row['title'],
                    'content': row['content'][:500] + '...' if len(row['content']) > 500 else row['content'],
                    'date': row['date'],
                    'permalink': row['permalink'],
                    'categories': row['categories'],
                    'tags': row['tags'],
                    'similarity_score': float(row['_distance']) if '_distance' in row else None
                })
            
            return {
                'success': True,
                'data': {
                    'posts': similar_posts,
                    'count': len(similar_posts)
                }
            }
            
        except Exception as e:
            return {
                'success': False,
                'data': f'Failed to search embeddings: {str(e)}'
            }
    
    def get_stats(self) -> Dict[str, Any]:
        """Get database statistics"""
        try:
            if self.table_name not in self.db.table_names():
                return {
                    'success': True,
                    'data': {
                        'table_exists': False,
                        'total_posts': 0,
                        'db_size_mb': 0
                    }
                }
            
            table = self.db.open_table(self.table_name)
            
            # Get table statistics
            total_posts = len(table)
            
            # Calculate database size
            db_size_mb = 0
            try:
                import os
                db_size_bytes = sum(
                    os.path.getsize(os.path.join(self.db_path, f))
                    for f in os.listdir(self.db_path)
                    if f.endswith('.lance')
                )
                db_size_mb = round(db_size_bytes / (1024 * 1024), 2)
            except:
                pass
            
            return {
                'success': True,
                'data': {
                    'table_exists': True,
                    'total_posts': total_posts,
                    'db_size_mb': db_size_mb,
                    'table_name': self.table_name,
                    'db_path': self.db_path
                }
            }
            
        except Exception as e:
            return {
                'success': False,
                'data': f'Failed to get stats: {str(e)}'
            }
    
    def check_existing_embeddings(self, post_ids: List[int]) -> Dict[str, Any]:
        """Check which post IDs already have embeddings in the database"""
        try:
            if self.table_name not in self.db.table_names():
                return {
                    'success': True,
                    'data': {
                        'existing_ids': [],
                        'missing_ids': post_ids
                    }
                }
            
            table = self.db.open_table(self.table_name)
            
            # Use a single query to get all existing post IDs
            if not post_ids:
                return {
                    'success': True,
                    'data': {
                        'existing_ids': [],
                        'missing_ids': []
                    }
                }
            
            # Build query for all post IDs at once
            id_conditions = " OR ".join([f"id = {pid}" for pid in post_ids])
            results = table.search().where(f"({id_conditions})").to_pandas()
            
            # Extract existing IDs from results
            existing_ids = results['id'].tolist() if not results.empty else []
            
            # Find missing IDs
            missing_ids = [pid for pid in post_ids if pid not in existing_ids]
            
            return {
                'success': True,
                'data': {
                    'existing_ids': existing_ids,
                    'missing_ids': missing_ids
                }
            }
            
        except Exception as e:
            return {
                'success': False,
                'data': f'Failed to check existing embeddings: {str(e)}'
            }
    
    def get_embeddings_by_ids(self, post_ids: List[int]) -> Dict[str, Any]:
        """Get embeddings for specific post IDs"""
        try:
            if self.table_name not in self.db.table_names():
                return {
                    'success': False,
                    'data': 'No embeddings table found'
                }
            
            table = self.db.open_table(self.table_name)
            
            # Build query for multiple IDs
            id_conditions = " OR ".join([f"id = {pid}" for pid in post_ids])
            results = table.search().where(f"({id_conditions})").to_pandas()
            
            embeddings = {}
            for _, row in results.iterrows():
                embeddings[int(row['id'])] = {
                    'embedding': row['embedding'].tolist() if hasattr(row['embedding'], 'tolist') else list(row['embedding']),
                    'title': row['title'],
                    'content': row['content'],
                    'date': row['date'],
                    'permalink': row['permalink'],
                    'categories': row['categories'],
                    'tags': row['tags']
                }
            
            return {
                'success': True,
                'data': embeddings
            }
            
        except Exception as e:
            return {
                'success': False,
                'data': f'Failed to get embeddings by IDs: {str(e)}'
            }
    
    def upsert_embeddings(self, posts: List[Dict], embeddings: List[List[float]]) -> Dict[str, Any]:
        """Upsert post embeddings (insert new, update existing)"""
        try:
            self.create_table_if_not_exists()
            table = self.db.open_table(self.table_name)
            
            # Prepare data for upsert
            data = []
            post_ids = []
            for post, embedding in zip(posts, embeddings):
                post_ids.append(post['id'])
                data.append({
                    'id': post['id'],
                    'title': post['title'],
                    'content': post['content'],
                    'date': post['date'],
                    'permalink': post['permalink'],
                    'categories': post.get('categories', []),
                    'tags': post.get('tags', []),
                    'embedding': embedding,
                    'created_at': datetime.now().replace(microsecond=datetime.now().microsecond)  # Ensure microsecond precision
                })
            
            # Delete existing records with the same IDs to avoid duplicates
            if post_ids:
                id_conditions = " OR ".join([f"id = {pid}" for pid in post_ids])
                table.delete(f"({id_conditions})")
            
            # Add new data
            table.add(data)
            
            return {
                'success': True,
                'data': f'Upserted {len(data)} embeddings in LanceDB'
            }
            
        except Exception as e:
            return {
                'success': False,
                'data': f'Failed to upsert embeddings: {str(e)}'
            }


def main():
    """Main function to handle LanceDB operations"""
    if len(sys.argv) < 3:
        print(json.dumps({
            'success': False,
            'data': 'Usage: python lancedb_operations.py <input_file> <operation>'
        }))
        sys.exit(1)
    
    input_file = sys.argv[1]
    operation = sys.argv[2]
    
    try:
        # Read input data
        with open(input_file, 'r', encoding='utf-8') as f:
            data = json.load(f)
        
        # Initialize LanceDB manager
        db_path = data.get('db_path', '/tmp/lancedb')
        table_name = data.get('table_name', 'wordpress_posts')
        manager = LanceDBManager(db_path, table_name)
        
        # Execute operation
        if operation == 'store':
            posts = data.get('posts', [])
            embeddings = data.get('embeddings', [])
            result = manager.store_embeddings(posts, embeddings)
            
        elif operation == 'search':
            query_embedding = data.get('query_embedding', [])
            limit = data.get('limit', 5)
            filters = data.get('filters', {})
            result = manager.search_similar(query_embedding, limit, filters)
            
        elif operation == 'stats':
            result = manager.get_stats()
            
        elif operation == 'check_existing_embeddings':
            post_ids = data.get('post_ids', [])
            result = manager.check_existing_embeddings(post_ids)
            
        elif operation == 'get_embeddings_by_ids':
            post_ids = data.get('post_ids', [])
            result = manager.get_embeddings_by_ids(post_ids)
            
        elif operation == 'upsert_embeddings':
            posts = data.get('posts', [])
            embeddings = data.get('embeddings', [])
            result = manager.upsert_embeddings(posts, embeddings)
            
        else:
            result = {
                'success': False,
                'data': f'Unknown operation: {operation}'
            }
        
        # Output result
        print(json.dumps(result, ensure_ascii=False))
        
    except Exception as e:
        print(json.dumps({
            'success': False,
            'data': f'Error: {str(e)}'
        }))


if __name__ == '__main__':
    main() 