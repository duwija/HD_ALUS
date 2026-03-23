#!/usr/bin/env python3
"""
Script to remove inline <style> blocks from Blade template files
and ensure they use external CSS (financial-reports.css)
"""

import re
import os

# Files to process
files_to_process = [
    "resources/views/jurnal/bukubesar.blade.php",
    "resources/views/jurnal/neraca_saldo.blade.php",
    "resources/views/jurnal/neraca.blade.php",
    "resources/views/jurnal/laba_rugi.blade.php",
    "resources/views/jurnal/arus_kas.blade.php",
    "resources/views/jurnal/perubahan_modal.blade.php",
]

base_path = "/var/www/html/adiyasa.alus.co.id/"

def remove_inline_styles(content):
    """Remove all <style>...</style> blocks from content"""
    # Pattern to match <style> tags and their content (including nested)
    pattern = r'<style[^>]*>.*?</style>\s*'
    cleaned = re.sub(pattern, '', content, flags=re.DOTALL)
    return cleaned

def process_file(filepath):
    """Process a single file to remove inline styles"""
    full_path = os.path.join(base_path, filepath)
    
    if not os.path.exists(full_path):
        print(f"❌ File not found: {filepath}")
        return False
    
    print(f"📝 Processing: {filepath}")
    
    # Read file content
    with open(full_path, 'r', encoding='utf-8') as f:
        content = f.read()
    
    # Count style blocks before removal
    style_count = len(re.findall(r'<style[^>]*>.*?</style>', content, flags=re.DOTALL))
    
    if style_count == 0:
        print(f"   ✓ No inline styles found")
        return True
    
    # Remove inline styles
    cleaned_content = remove_inline_styles(content)
    
    # Write back
    with open(full_path, 'w', encoding='utf-8') as f:
        f.write(cleaned_content)
    
    print(f"   ✓ Removed {style_count} style block(s)")
    return True

def main():
    print("=" * 60)
    print("Removing Inline Styles from Financial Report Templates")
    print("=" * 60)
    print()
    
    success_count = 0
    for filepath in files_to_process:
        if process_file(filepath):
            success_count += 1
        print()
    
    print("=" * 60)
    print(f"✅ Successfully processed {success_count}/{len(files_to_process)} files")
    print("=" * 60)
    print()
    print("📌 Note: All files now use external CSS: public/css/financial-reports.css")
    print("📌 Run: php artisan view:clear to apply changes")

if __name__ == "__main__":
    main()
