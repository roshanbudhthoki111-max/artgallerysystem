#!/bin/bash
# Create placeholder SVG images for demo purposes

# placeholder.jpg (main artwork placeholder)
cat > /home/claude/art-gallery/assets/img/placeholder.svg << 'EOF'
<svg xmlns="http://www.w3.org/2000/svg" width="800" height="600" viewBox="0 0 800 600">
  <rect width="800" height="600" fill="#f3f1ee"/>
  <rect x="340" y="240" width="120" height="120" rx="8" fill="#e8e4df" stroke="#d4af7a" stroke-width="2"/>
  <text x="400" y="310" font-family="Georgia,serif" font-size="32" fill="#b8904a" text-anchor="middle">◈</text>
  <text x="400" y="380" font-family="Georgia,serif" font-size="14" fill="#888" text-anchor="middle">Artwork Preview</text>
</svg>
EOF

# default-avatar.png placeholder
cat > /home/claude/art-gallery/assets/img/default-avatar.svg << 'EOF'
<svg xmlns="http://www.w3.org/2000/svg" width="200" height="200" viewBox="0 0 200 200">
  <rect width="200" height="200" fill="#f3f1ee" rx="100"/>
  <circle cx="100" cy="80" r="35" fill="#d4c4a8"/>
  <ellipse cx="100" cy="160" rx="55" ry="45" fill="#d4c4a8"/>
</svg>
EOF

echo "Placeholder images created"
