# Get the list of unique postcodes from the CSV file downloaded from australia post
@postcodes = {}
@names = []
File.new('pc-full.csv').readlines.each {|l|
  values = l.gsub('"', '').split(/ *, */)
  postcode = values[0]
  type = values[9].strip()
  # Google doesn't have coordinate for postcodes that are not "Delivery Area"
  if type != 'Delivery Area'
    next
  end
  if postcode.match(/^[0-9]+$/)
    postcode = "#{"%04s" % postcode}"
    if !@postcodes[postcode]
      @postcodes[postcode] = {'state' => values[2], 'latitude' => '', 'longitude' => ''}
    end
    @names.push({'name' => values[1], 'state' => values[2], 'type' => type, 'postcode' => postcode})
  end
}
puts "Total #{@postcodes.length} unique postcodes"
puts "Total #{@names.length} place names"

# Scrape them all from google maps if we don't already have the data
if !File.directory?('data')
  Dir.mkdir('data')
end

@postcodes.each {|postcode, details|
  fileName = "data/#{postcode}.txt"
  if !File.exists?(fileName)
    system "curl -o #{fileName} \"http://maps.google.com.au/" +
      "maps?f=q&hl=en&geocode=&q=postcode+#{details['state']}+australia&output=js\" 2> /dev/null"
  end
  failed = false
  if File.exist?(fileName)
    f = File.new(fileName)
    lines = f.read
    f.close
    if m = /center:\{lat:([\-.0-9]*),lng:([\-.0-9]*)\}/.match(lines)
      @postcodes[postcode]['latitude'] = m[1]
      @postcodes[postcode]['longitude'] = m[2]
    else
      failed = 'Invalid'
    end
  else
    failed = 'Missing'
  end
  if failed
    puts "#{fileName}: #{failed}"
    File.unlink(fileName)
  end
}

# Write it out to a CSV file
outFile = "../../data/au/au-postcode-data.csv"
File.open(outFile,"w") {|f|
  f.write "Postcode,State,Name,Type,Lat,Lng\n"
  @names.each {|details|
    postcode = @postcodes[details['postcode']]
    if postcode['latitude']
      f.write "#{details['postcode']},#{details['state']},#{details['name']},#{details['type']},#{postcode['latitude']},#{postcode['longitude']}\n"
    end
  }
}
puts "Wrote #{outFile}"
