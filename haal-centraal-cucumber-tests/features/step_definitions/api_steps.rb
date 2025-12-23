# Haal Centraal API Step Definitions

require 'httparty'
require 'json'
require 'json-schema'

API_BASE_URL = ENV['API_URL'] || 'http://localhost:8080/apps/openregister'

class ApiClient
  include HTTParty
  base_uri API_BASE_URL
  
  headers 'Content-Type' => 'application/json'
  headers 'Accept' => 'application/json'
  
  def self.get(endpoint)
    super(endpoint)
  end
end

# Store response globally
$last_response = nil
$last_status = nil

Given(/^de API is beschikbaar op "([^"]*)"$/) do |url|
  # Test connectivity
  begin
    response = HTTParty.get("#{url}/ingeschrevenpersonen", timeout: 5)
    # Accept any status code as long as we get a response
  rescue => e
    raise "API niet bereikbaar op #{url}: #{e.message}"
  end
end

Given(/^er bestaat een persoon met BSN "([^"]*)"$/) do |bsn|
  # Check if person exists
  response = ApiClient.get("/ingeschrevenpersonen/#{bsn}")
  if response.code != 200
    puts "⚠️  Waarschuwing: Persoon met BSN #{bsn} bestaat mogelijk niet in de database"
  end
end

When(/^ik een GET request doe naar "([^"]*)"$/) do |endpoint|
  begin
    $last_response = ApiClient.get(endpoint)
    $last_status = $last_response.code
  rescue => e
    $last_response = nil
    $last_status = 500
    raise "Request gefaald: #{e.message}"
  end
end

Then(/^de response status code is (\d+)$/) do |expected_status|
  expect($last_status).to eq(expected_status.to_i), 
    "Verwachtte status #{expected_status}, maar kreeg #{$last_status}. Response: #{$last_response&.body}"
end

Then(/^de response bevat "([^"]*)"$/) do |key|
  expect($last_response).not_to be_nil, "Geen response ontvangen"
  
  parsed = JSON.parse($last_response.body)
  keys = key.split('.')
  value = parsed
  
  keys.each do |k|
    expect(value).to have_key(k), "Response bevat geen '#{key}'. Beschikbare keys: #{parsed.keys.join(', ')}"
    value = value[k]
  end
end

Then(/^de response bevat niet "([^"]*)"$/) do |key|
  expect($last_response).not_to be_nil, "Geen response ontvangen"
  
  parsed = JSON.parse($last_response.body)
  keys = key.split('.')
  value = parsed
  
  keys.each do |k|
    if value.is_a?(Hash) && value.has_key?(k)
      value = value[k]
    else
      return # Key niet gevonden, test slaagt
    end
  end
  
  # Als we hier komen, bestaat de key wel
  raise "Response bevat onverwacht '#{key}'"
end

Then(/^de waarde van "([^"]*)" is "([^"]*)"$/) do |key, expected_value|
  expect($last_response).not_to be_nil, "Geen response ontvangen"
  
  parsed = JSON.parse($last_response.body)
  keys = key.split('.')
  value = parsed
  
  keys.each do |k|
    expect(value).to have_key(k), "Response bevat geen '#{key}'"
    value = value[k]
  end
  
  expect(value.to_s).to eq(expected_value), 
    "Verwachtte waarde '#{expected_value}' voor '#{key}', maar kreeg '#{value}'"
end

Then(/^"([^"]*)" is een array$/) do |key|
  expect($last_response).not_to be_nil, "Geen response ontvangen"
  
  parsed = JSON.parse($last_response.body)
  keys = key.split('.')
  value = parsed
  
  keys.each do |k|
    expect(value).to have_key(k), "Response bevat geen '#{key}'"
    value = value[k]
  end
  
  expect(value).to be_a(Array), "Verwachtte array voor '#{key}', maar kreeg #{value.class}"
end

Then(/^"([^"]*)" bevat "([^"]*)"$/) do |parent_key, child_key|
  expect($last_response).not_to be_nil, "Geen response ontvangen"
  
  parsed = JSON.parse($last_response.body)
  parent_keys = parent_key.split('.')
  parent_value = parsed
  
  parent_keys.each do |k|
    expect(parent_value).to have_key(k), "Response bevat geen '#{parent_key}'"
    parent_value = parent_value[k]
  end
  
  expect(parent_value).to be_a(Hash), "Verwachtte object voor '#{parent_key}', maar kreeg #{parent_value.class}"
  expect(parent_value).to have_key(child_key), 
    "'#{parent_key}' bevat geen '#{child_key}'. Beschikbare keys: #{parent_value.keys.join(', ')}"
end







