import sys
import re

fn = '/etc/nginx/conf.d/perumnet.alus.co.id.conf'
with open(fn, 'r') as f:
    s = f.read()

# Pattern to match the broken or existing proxy location block
# It starts with 'location ~ ^/proxy/' and ends with the matching closing brace
pattern = r'location ~ \^/proxy/.*?\{.*?\}\n*'
new_block = r'''location ~ ^/proxy/([0-9\.]+)/([0-9]+)/([0-9\.]+)(/.*)?$ {
    set $target_ip $1;
    set $target_port $2;
    set $target_host $3;
    set $upstream_path $4;

    if ($upstream_path = '') {
        set $upstream_path '/';
    }

    proxy_set_header Host $target_host;
    proxy_set_header X-Real-IP $remote_addr;
    proxy_set_header X-Forwarded-For $proxy_add_x_forwarded_for;
    proxy_http_version 1.1;
    proxy_set_header Connection '';

    proxy_pass http://$target_ip:$target_port$upstream_path;
    proxy_read_timeout 90;
    proxy_connect_timeout 5;
    proxy_redirect off;
}
'''

# We need a more robust way to find the block because of nested braces potential
# or just look for the first occurrence of the proxy location and replace until its closing brace.
start_marker = 'location ~ ^/proxy/'
loc_start = s.find(start_marker)
if loc_start != -1:
    # Find opening brace
    i = s.find('{', loc_start)
    if i != -1:
        count = 1
        j = i + 1
        while j < len(s) and count > 0:
            if s[j] == '{': count += 1
            elif s[j] == '}': count -= 1
            j += 1
        # Also consume any double-closing braces if they were accidentally added
        # In the previous cat output, it seems there are extra fragments.
        # Let's just replace from loc_start to the end of that block.
        
        # Looking at the cat output, there's another fragment:
        # proxy_set_header Host ; ... proxy_redirect off; }
        # Let's try to find the last 'proxy_redirect off;\n}' after loc_start.
        
        end_marker = 'proxy_redirect off;\n}'
        loc_end = s.find(end_marker, loc_start)
        if loc_end != -1:
            loc_end += len(end_marker)
            ns = s[:loc_start] + new_block + s[loc_end:]
        else:
            # Fallback to brace counting
            ns = s[:loc_start] + new_block + s[j:]
    else:
        print("Could not find opening brace")
        sys.exit(1)
else:
    print("Could not find start marker")
    sys.exit(1)

with open('/tmp/nginx.conf.fixed', 'w') as f:
    f.write(ns)
