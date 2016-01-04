# FourOneOne
A small website to store a collection of territories, add multi-unit dwellings ( apartments ) and do a reverse lookup at 411.ca using the address to parse out the microformat data, adding people to the buildling. ( AKA, it stores and organizes numbers from 411.ca )

##Dependencies
All dependencies are listed in composer. Run `composer update` in the project directory to download packages. and `require 'vendor/autoload.php'` in your code. See Composer documentation for more details.

##Technologies used

 - `RedBean` for Data Layer
 - `Slim Framework` for the ( Controller / dispatcher )
 - `Twig` for the templates ( View Layer )
 - `MicrodataPHP` to extract numbers from 411.ca

##Overvuew
Uses Buildling Address to create a URL for `411.ca`, passes that URL to `MicrodataPHP` and allows user to Add people to Data store.
