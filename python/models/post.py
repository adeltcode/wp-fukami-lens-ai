import json

class Post:
    def __init__(self, data):
        self.id = data.get('ID', '')
        self.title = data.get('title', {}).get('rendered', '')
        self.date = data.get('date', '')
        self.content = data.get('content', {}).get('rendered', '')
        self.permalink = data.get('permalink', '')
        self.categories = data.get('categories', [])
        self.tags = data.get('tags', [])

def load_posts_from_json(path):
    with open(path, 'r', encoding='utf-8') as f:
        data = json.load(f)
    return [Post(post) for post in data] 