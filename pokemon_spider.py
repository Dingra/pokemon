import scrapy

#The purpose of this spider is to gather the names of all current pokemon.
#As there are currently more than 800 Pokemon, entering them all into the database manually
#was not practical. I ran this spider to scrape from pokemondb.net and saved all of the pokemon in a .json file and
#created a separate file called add_pokemon.py to add them to the database
class QuotesSpider(scrapy.Spider):
    name = "pokemon"
    start_urls = [
        'http://pokemondb.net/pokedex/national',
    ]

    def parse(self, response):
	for pokemon in response.css('span.infocard-tall'):
	        yield {
			'natDexId': pokemon.css('small::text').extract_first(),
        	        'name': pokemon.css('a.ent-name::text').extract_first(),
			'type1': pokemon.css('small.aside a.itype:nth-child(1)::text').extract_first(),
			'type2': pokemon.css('small.aside a.itype:nth-child(2)::text').extract_first(),
        	 }
