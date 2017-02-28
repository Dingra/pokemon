import json
import MySQLdb

db = MySQLdb.connect(host="localhost", port=3306, user="root", passwd="<password>", db="pokemon")

def parseStringId(natDexId):
        retId = int(natDexId[1:])
	return retId

#add pokemon from  pokemon.json to the database. The pokemon.json file was created using a separate file pokemon_spider.py
with open('pokemon.json') as json_data:
	d = json.load(json_data)

	for pokemon in d:
		natDexId = parseStringId(pokemon['natDexId'])
		name = pokemon['name']

		#Pokemon types are stored in lower case in the database
		type1 = pokemon['type1'].lower()

		#Not all pokemon have a second type so cases where they don't have to be handled separately
		if(pokemon['type2']):
			type2 = pokemon['type2'].lower()
		else:
			type2 = ""

		output = "hopefully just added: " + name.lower() + ", " + type1.lower()
		generation = 6

		cursor = db.cursor()

		query = 'insert into pokemon VALUES(%d, null, "%s", "%s", "%s", null, null, null, null, null, %d)' % (natDexId, name, type1, type2, generation)
		cursor.execute(query)
		db.commit()

		if (type2):
			output = output + ", " + type2.lower()

		print output

db.close()
