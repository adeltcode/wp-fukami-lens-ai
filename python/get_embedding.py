#!/usr/bin/env python3
"""
Get Embeddings for WP Fukami Lens AI

This script gets embeddings from OpenAI's API for text content.
"""

import sys
import json
import os
import requests
from typing import List

# Set environment variables for HuggingFace cache
os.environ["HF_HOME"] = "/tmp"
os.environ["HF_HUB_CACHE"] = "/tmp/huggingface"
os.environ["XDG_CACHE_HOME"] = "/tmp"


def get_openai_embedding(text: str, model: str, api_key: str) -> List[float]:
    """Get embedding from OpenAI API"""
    url = "https://api.openai.com/v1/embeddings"
    
    headers = {
        "Authorization": f"Bearer {api_key}",
        "Content-Type": "application/json"
    }
    
    data = {
        "input": text,
        "model": model
    }
    
    try:
        response = requests.post(url, headers=headers, json=data, timeout=30)
        response.raise_for_status()
        
        result = response.json()
        return result['data'][0]['embedding']
        
    except requests.exceptions.RequestException as e:
        raise Exception(f"OpenAI API request failed: {str(e)}")
    except (KeyError, IndexError) as e:
        raise Exception(f"Unexpected response format: {str(e)}")


def main():
    """Main function to get embedding"""
    if len(sys.argv) < 2:
        print(json.dumps({
            'success': False,
            'data': 'Usage: python get_embedding.py <input_file>'
        }))
        sys.exit(1)
    
    input_file = sys.argv[1]
    
    try:
        # Read input data
        with open(input_file, 'r', encoding='utf-8') as f:
            data = json.load(f)
        
        text = data.get('text', '')
        model = data.get('model', 'text-embedding-3-small')
        api_key = data.get('api_key', '')
        
        if not text:
            raise Exception('No text provided')
        
        if not api_key:
            raise Exception('No API key provided')
        
        # Get embedding
        embedding = get_openai_embedding(text, model, api_key)
        
        # Return result
        result = {
            'success': True,
            'data': {
                'embedding': embedding,
                'model': model,
                'text_length': len(text)
            }
        }
        
        print(json.dumps(result, ensure_ascii=False))
        
    except Exception as e:
        print(json.dumps({
            'success': False,
            'data': f'Error: {str(e)}'
        }))


if __name__ == '__main__':
    main() 