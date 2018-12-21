# map-generator
map-generator is a custom solution to generating infinitely large, procedurally generated, tile based maps using a custom OpenSimplex noise generator based on the tetrahedral-octahedral honeycomb.

## Try it Out
A demo of this is available at https://jerobertson.co.uk/projects/map/.

## Navigating the Map
By using the arrow keys or swiping up, down, left, or right on the canvas, you can move the centre of the map by a tile. This is designed for a game in which you can explore a map.

## Render Layers
Different layers of the map can be shown or hidden by selecting the checkbox below the canvas.

### Temperature
Displays the noise generation for the temperature of the map.

<img src="https://jerobertson.co.uk/projects/map/img/about/temperature.png" width="50%"/>

### Rainfall
Displays the noise generation for the rainfall of the map.

<img src="https://jerobertson.co.uk/projects/map/img/about/rainfall.png" width="50%"/>

### Biomes
By combining temperature and rainfall values, biomes can be generated.

<img src="https://jerobertson.co.uk/projects/map/img/about/biomes.png" width="50%"/>

### Heightmap
Maps are actually generated using the heightmap and the rainmap. Temperature is actually just the inverse of the heightmap. This layer can be used to provide shadows and highlights.

<img src="https://jerobertson.co.uk/projects/map/img/about/heightmap.png" width="50%"/>

### Tile Borders
This layer renders borders between each tile. Not recommended for larger map sizes.

<img src="https://jerobertson.co.uk/projects/map/img/about/tileborders.png" width="50%"/>

### Water
Choose to render water or not. Water is defined using the heightmap between 0 and a defined level up to 1.

Sand is also rendered around the water up to 1.2x the max water level if the Biomes layer is checked.

<img src="https://jerobertson.co.uk/projects/map/img/about/water.png" width="50%"/>

## Visibility Options
Map tiles can either be shown or hidden using the 'Displayed' or 'Discoverable' radio buttons.

### Displayed Map
Renders all tiles.

<img src="https://jerobertson.co.uk/projects/map/img/about/displayed.png" width="50%"/>

### Discoverable Map
Hides tiles unless the user has discovered them by moving to a tile nearby.

<img src="https://jerobertson.co.uk/projects/map/img/about/discoverable.png" width="40%"/>

<img src="https://jerobertson.co.uk/projects/map/img/about/radius4.png" width="40%"/>

#### Discovery Radius
Tiles are discovered or not based on their distance from the centre of the map. Setting the 'Discovery Radius' to 0 means that only the centre tile is discovered; larger radiuses discover more tiles.

#### Clearing Tiles
Once discovered, a tile is always visible. Selecting the 'Clear Tiles' button completely forgets all discovered tiles and only re-renders those at the centre of the map within the discovery radius.

## Map Size
The 'Map Size' determines both the width and the height of the map. It has a range between 3 and 51.

### Giant Map
Selecting 'Giant Map' sets the Map Size to 201. This is useful for drawing more realistic maps.

<img src="https://jerobertson.co.uk/projects/map/img/about/size17.png" width="30%"/>

<img src="https://jerobertson.co.uk/projects/map/img/about/size51.png" width="30%"/>

<img src="https://jerobertson.co.uk/projects/map/img/about/size201.png" width="30%"/>

## Noise Generation Settings
The six noise generation settings sliders determine the shape and biome distribution of the final map.

### Scale
The scale determines how zoomed into a map you are.

<img src="https://jerobertson.co.uk/projects/map/img/about/scale5.png" width="40%"/>

<img src="https://jerobertson.co.uk/projects/map/img/about/scale10.png" width="40%"/>

### Octaves
By generating multiple layers of noise at differing frequencies and merging them together we get a more rugged pattern.

<img src="https://jerobertson.co.uk/projects/map/img/about/octave1.png" width="30%"/>

<img src="https://jerobertson.co.uk/projects/map/img/about/octave2.png" width="30%"/>

<img src="https://jerobertson.co.uk/projects/map/img/about/octaver.png" width="30%"/>

<img src="https://jerobertson.co.uk/projects/map/img/about/octaveb1.png" width="40%"/>

<img src="https://jerobertson.co.uk/projects/map/img/about/octaveb5.png" width="40%"/>

Both the heightmap and the rainfall layers can have up to five octaves each.

### Powers
Powers modify the curve of the noise generation, making higher or lower values more likely depending on the setting.

<img src="https://jerobertson.co.uk/projects/map/img/about/power5.png" width="30%"/>

<img src="https://jerobertson.co.uk/projects/map/img/about/power10.png" width="30%"/>

<img src="https://jerobertson.co.uk/projects/map/img/about/power20.png" width="30%"/>

Both the heightmap and the rainmap can have their curve adjusted in this way.

### Water Level
The heightmap ranges between 0 and 1. Anything below the level defined by the water level slider is converted into water if the water layer is checked.

<img src="https://jerobertson.co.uk/projects/map/img/about/water25.png" width="40%"/>

<img src="https://jerobertson.co.uk/projects/map/img/about/water50.png" width="40%"/>

## Creating and Loading Maps
When first visiting this website, a unique ID is generated for you. Any changes you make to the map are stored on the database and linked to your ID. You are able to save multiple maps to your ID and retrieve them using the three links at the bottom of the page.

### New Maps
Selecting the 'new map' link will open a dialog that allows you to define a new map. It has two options.

**Map Name** - The name of the map as it appears in the loading map dialog.

**Seed** - A random number between -9007199254740991 and 9007199254740991. This is the seed used by the random number generator that generates the layers of the map. If a map has the same seed, it will always look identical from the same co-ordinates.

### Loading Maps
Selecting the 'load map' link will open a dialog that allows you to load a pre-existing map. It has two options.

**My ID** - Entering an ID here and selecting 'Search' will search for all the maps linked with this ID. Loading any map from a different ID will set your ID to the ID linked with the map. Don't share your ID if you don't want someone else modifying your maps!

**Map Name** - The name of the map to load. Selecting 'Delete' will instantly and irrecoverably delete the map selected!

### Cookies
Selecting the 'save map' link will open a dialog that allows you to set your cookie preferences.

By default, your ID is stored as a session cookie and is forgotten when you close the browser. By agreeing to have a cookie stored on your computer, your ID is remembered for up to 10 years from your last access date, so you don't have to worry about forgetting your ID.