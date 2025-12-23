# Cucumber environment configuration

require 'httparty'
require 'json'
require 'rspec/expectations'

# Set default timeout
HTTParty::Basement.default_timeout(30)

# Configure test environment
API_BASE_URL = ENV['API_URL'] || 'http://localhost:8080/apps/openregister'

puts "=" * 60
puts "Haal Centraal Cucumber Tests"
puts "=" * 60
puts "API URL: #{API_BASE_URL}"
puts "=" * 60
puts ""

# Before hook - run before each scenario
Before do |scenario|
  # Reset global state
  $last_response = nil
  $last_status = nil
end

# After hook - run after each scenario
After do |scenario|
  if scenario.failed?
    puts "\n❌ Scenario gefaald: #{scenario.name}"
    if $last_response
      puts "Response status: #{$last_status}"
      puts "Response body:"
      begin
        parsed = JSON.parse($last_response.body)
        puts JSON.pretty_generate(parsed)
      rescue
        puts $last_response.body
      end
    end
  end
end

# After all scenarios
at_exit do
  puts "\n" + "=" * 60
  puts "Tests voltooid"
  puts "=" * 60
end







