#!/bin/bash

# Speech Contest Stable Server
# Prevents broken pipe errors during file uploads

echo "🚀 Starting Speech Contest Stable Server..."
echo "📁 Project: /Applications/XAMPP/xamppfiles/htdocs/speech-contest"
echo "🌐 URL: http://127.0.0.1:8080"
echo "🛑 Press Ctrl+C to stop"
echo ""

# Kill any existing process on port 8080
lsof -ti:8080 | xargs kill -9 2>/dev/null || true

# Start the custom server
php -S 127.0.0.1:8080 /Applications/XAMPP/xamppfiles/htdocs/speech-contest/custom-server.php
