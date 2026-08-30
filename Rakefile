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
# dtb/ is excluded because its playlist pages are generated on the server and do
# not exist locally -- this task's --delete would wipe them. Deploy it with
# `rake publish_dtb` instead.
desc 'Publish the site to the server'
task :publish do
  puts 'Publishing site...'
  sh "rsync -avzzh --progress --delete --exclude .git --exclude Rakefile --exclude data/ --exclude media/ --exclude dtb/ ./public/ aryn:arynmichelle.com/"
    sh "rsync -avzzh --progress --delete aryn:arynmichelle.com/data/ ./public/data/"
    sh "rsync -avzzh --progress --delete aryn:arynmichelle.com/media/ ./public/media/"
end

# Publish the playlist manager. No --delete: the server holds the uploaded audio
# and the generated <token>/<playlist>/ pages, none of which exist locally.
# Retiring a source file therefore means removing it on the server by hand.
desc 'Publish the playlist manager code (leaves uploads and generated pages alone)'
task :publish_dtb do
  puts 'Publishing playlist manager...'
  sh "rsync -avzzh --progress --exclude .git --exclude data/ --exclude media/ ./public/dtb/ aryn:arynmichelle.com/dtb/"
end