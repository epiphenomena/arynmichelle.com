require 'fileutils'

# Default task
task default: :publish

# Start development server
desc 'Start the development server'
task :serve do
  port = ENV['PORT'] || 8000
  puts "Starting development server on http://localhost:#{port}"
  puts 'Press Ctrl+C to stop the server'

  # Start PHP built-in server
  system("php", "-S", "localhost:#{port}", "-t", "./public/")
end

# Publish the site (stub - implement based on your deployment method)
desc 'Publish the site to the server'
task :publish do
  puts 'Publishing site...'
  sh "rsync -avzzh --progress --delete --exclude .git --exclude Rakefile --exclude data/ --exclude media/ ./public/ aryn:arynmichelle.com/"
    sh "rsync -avzzh --progress --delete aryn:arynmichelle.com/data/ ./public/data/"
    sh "rsync -avzzh --progress --delete aryn:arynmichelle.com/media/ ./public/media/"
end