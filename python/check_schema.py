#!/usr/bin/env python3
"""
Check and fix LanceDB schema for WP Fukami Lens AI

This script checks the current table schema and fixes timestamp precision issues.
"""

import sys
import json
import os
import lancedb
import pyarrow as pa
from datetime import datetime

# Set environment variables for HuggingFace cache
os.environ["HF_HOME"] = "/tmp"
os.environ["HF_HUB_CACHE"] = "/tmp/huggingface"
os.environ["XDG_CACHE_HOME"] = "/tmp"

def check_and_fix_schema(db_path, table_name):
    """Check current schema and fix if needed"""
    try:
        db = lancedb.connect(db_path)
        
        if table_name not in db.table_names():
            print(json.dumps({
                'success': False,
                'data': f'Table {table_name} does not exist'
            }))
            return
        
        table = db.open_table(table_name)
        
        # Get current schema
        current_schema = table.schema
        print(f"Current schema: {current_schema}")
        
        # Check if created_at field exists and its type
        created_at_field = None
        for field in current_schema:
            if field.name == 'created_at':
                created_at_field = field
                break
        
        if created_at_field:
            print(f"Created_at field type: {created_at_field.type}")
            
            # Check if it's timestamp[ms] (millisecond precision)
            if str(created_at_field.type) == 'timestamp[ms]':
                print("Found timestamp[ms] - need to recreate table with timestamp[us]")
                
                # Get all data from current table
                all_data = table.to_pandas()
                print(f"Current table has {len(all_data)} rows")
                
                # Drop the current table
                db.drop_table(table_name)
                print(f"Dropped table {table_name}")
                
                # Create new table with correct schema
                new_schema = pa.schema([
                    ('id', pa.int64()),
                    ('title', pa.string()),
                    ('content', pa.string()),
                    ('date', pa.string()),
                    ('permalink', pa.string()),
                    ('categories', pa.list_(pa.string())),
                    ('tags', pa.list_(pa.string())),
                    ('embedding', pa.list_(pa.float32(), 1536)),
                    ('created_at', pa.timestamp('us'))  # Use microsecond precision
                ])
                
                # Create empty table with new schema
                db.create_table(table_name, schema=new_schema)
                print(f"Created new table {table_name} with timestamp[us] schema")
                
                # Re-insert the data with microsecond timestamps
                if not all_data.empty:
                    # Convert timestamps to microsecond precision
                    all_data['created_at'] = all_data['created_at'].dt.floor('us')
                    
                    # Insert data back
                    new_table = db.open_table(table_name)
                    new_table.add(all_data.to_dict('records'))
                    print(f"Re-inserted {len(all_data)} rows with microsecond timestamps")
                
                return {
                    'success': True,
                    'data': f'Successfully recreated table with timestamp[us] schema. Re-inserted {len(all_data)} rows.'
                }
            else:
                print(f"Schema is already correct: {created_at_field.type}")
                return {
                    'success': True,
                    'data': f'Schema is already correct: {created_at_field.type}'
                }
        else:
            print("No created_at field found in schema")
            return {
                'success': True,
                'data': 'No created_at field found in schema'
            }
            
    except Exception as e:
        return {
            'success': False,
            'data': f'Error checking/fixing schema: {str(e)}'
        }

def main():
    """Main function"""
    if len(sys.argv) < 2:
        print(json.dumps({
            'success': False,
            'data': 'Usage: python check_schema.py <db_path>'
        }))
        sys.exit(1)
    
    db_path = sys.argv[1]
    table_name = 'wordpress_posts'
    
    result = check_and_fix_schema(db_path, table_name)
    print(json.dumps(result, ensure_ascii=False))

if __name__ == '__main__':
    main() 