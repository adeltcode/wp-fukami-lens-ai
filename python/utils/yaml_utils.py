def yaml_escape(s):
    if not isinstance(s, str):
        return s
    return s.replace('\\', '\\\\').replace('"', '\\"')

def build_yaml_front_matter(post):
    lines = [
        "---",
        f'id: {post.id}',
        f'title: "{yaml_escape(post.title)}"',
        f'date: "{post.date}"',
        f'permalink: "{post.permalink}"',
        "categories:",
    ] + [f'  - "{yaml_escape(cat)}"' for cat in post.categories] + [
        "tags:",
    ] + [f'  - "{yaml_escape(tag)}"' for tag in post.tags] + [
        "---",
        "",
    ]
    return '\n'.join(lines) 