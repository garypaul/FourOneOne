# FourOneOne
A small utility to grab details from 411.ca, parse out the microformat data, convert it to json and display it as a table.

Uses Address input to create a URL for `411.ca`, passes that URL encoded to `http://getschema.org/microdataextractor` to extract microdata in `JSON` form, then parses through entries and creates a nice table.

For more information on the microformat extractor, go here: http://www.getschema.org/microdataextractor/about
