function shadeColour(color, percent) {   
    var f=parseInt(color.slice(1),16),t=percent<0?0:255,p=percent<0?percent*-1:percent,R=f>>16,G=f>>8&0x00FF,B=f&0x0000FF;
    return "#"+(0x1000000+(Math.round((t-R)*p)+R)*0x10000+(Math.round((t-G)*p)+G)*0x100+(Math.round((t-B)*p)+B)).toString(16).slice(1);
}
function blendColours(c0, c1, p) {
    var f=parseInt(c0.slice(1),16),t=parseInt(c1.slice(1),16),R1=f>>16,G1=f>>8&0x00FF,B1=f&0x0000FF,R2=t>>16,G2=t>>8&0x00FF,B2=t&0x0000FF;
    return "#"+(0x1000000+(Math.round((R2-R1)*p)+R1)*0x10000+(Math.round((G2-G1)*p)+G1)*0x100+(Math.round((B2-B1)*p)+B1)).toString(16).slice(1);
}

function Map(mapLength = 17, temp = false, rain = false, biome = true, height = false, borders = true) {
    this.tileSize = 45
    this.renderTemp = temp
    this.renderRain = rain
    this.renderBiome = biome
    this.renderBiomeColour = 'redblob'
    this.renderHeight = height
    this.renderBorders = borders
    this.mapLength = mapLength
    this.renderLength = this.mapLength + 2
    this.progress = 0
    this.maxProgress = this.renderLength * this.renderLength
    this.getDrawSpace = function() {
        var maxHeight = 2 * Math.ceil(($(document).height() - $('#map-controls').outerHeight(true) - $(this.canvas).offset().top * 2) / 2) - 1
        var maxWidth = 2 * Math.ceil(($(document).width() - $(this.canvas).offset().top * 2) / 2) - 1
        var maxTile = this.tileSize * this.mapLength
        return Math.min(maxHeight, maxWidth, maxTile)
    }
    this.canvas = document.getElementById('canvas')
    this.canvas.height = this.getDrawSpace()
    this.canvas.width = this.getDrawSpace()
    this.context = this.canvas.getContext('2d')
    this.renderCanvas = document.createElement('canvas')
    this.renderCanvas.height = this.tileSize * this.renderLength
    this.renderCanvas.width = this.tileSize * this.renderLength
    this.renderContext = this.renderCanvas.getContext('2d')
    this.scale = this.canvas.width / (this.renderCanvas.width - this.tileSize * 2)
    this.context.scale(this.scale, this.scale)
    this.tempArray = {}
    this.rainArray = {}
    this.biomeArray = {}
    this.locationsArray = {}
    this.drawMap = function(offX, offY) {
        this.context.translate(offX, offY);
        this.context.drawImage(this.renderCanvas, -this.tileSize, -this.tileSize)
        this.context.translate(0 - offX, 0 - offY)
        this.context.drawImage(playerDotImage, this.tileSize * Math.floor(this.mapLength / 2), this.tileSize * Math.floor(this.mapLength / 2), this.tileSize, this.tileSize)
    }
    this.renderMap = function(dir) {
        var posX = 0;
        var posY = this.tileSize * (this.renderLength - 1);

        var oldImage = this.renderContext.getImageData(0, 0, this.renderCanvas.width, this.renderCanvas.height)
        switch(dir) {
            case 37:
                this.renderContext.putImageData(oldImage, this.tileSize, 0)
                break
            case 38:
                this.renderContext.putImageData(oldImage, 0, this.tileSize)
                break
            case 39:
                this.renderContext.putImageData(oldImage, -this.tileSize, 0)
                break
            case 40:
                this.renderContext.putImageData(oldImage, 0, -this.tileSize)
                break
            default:
                break
        }

        this.renderContext.lineWidth = 1 / this.scale

        for (var y = 0; y < this.renderLength; y++) {
            for (var x = 0; x < this.renderLength; x++) {
                var toFill = false
                this.renderContext.fillStyle = '#000000'
                if (x in this.biomeArray && y in this.biomeArray[x] && (this.renderBiome || this.biomeArray[x][y] == 10)) {
                    toFill = true
                    if (this.renderBiomeColour == 'whittaker') {
                        this.renderContext.fillStyle = whittakerColours[this.biomeArray[x][y]]
                    } else {
                        this.renderContext.fillStyle = redblobColours[this.biomeArray[x][y]]
                    }
                }
                if (this.renderTemp && x in this.tempArray && y in this.tempArray[x]) {
                    toFill = true
                    if (this.renderContext.fillStyle != '#000000') {
                        this.renderContext.fillStyle = blendColours(this.renderContext.fillStyle, '#ff0000', (this.tempArray[x][y] - 1) / -2)
                    } else {
                        this.renderContext.fillStyle = shadeColour('#ff0000', this.tempArray[x][y] * -1)
                    }
                }
                if (this.renderRain && x in this.rainArray && y in this.rainArray[x]) {
                    toFill = true
                    if (this.renderContext.fillStyle != '#000000') {
                        this.renderContext.fillStyle = blendColours(this.renderContext.fillStyle, '#0000ff', this.rainArray[x][y] / 2)
                    } else {
                        this.renderContext.fillStyle = shadeColour('#0000ff', this.rainArray[x][y] - 1)
                    }
                }
                if (this.renderHeight && x in this.tempArray && y in this.tempArray[x]) {
                    toFill = true
                    if (this.renderContext.fillStyle != '#000000') {
                        this.renderContext.fillStyle = shadeColour(this.renderContext.fillStyle, this.tempArray[x][y] - 0.7)
                    } else {
                        this.renderContext.fillStyle = shadeColour('#ffffff', this.tempArray[x][y] - 1)
                    }
                }
                if (toFill) {
                    this.renderContext.fillRect(posX, posY, this.tileSize, this.tileSize)
                    if (this.renderBorders && !this.biomeArray[x][y] == 0) this.renderContext.strokeRect(posX, posY, this.tileSize, this.tileSize)
                }
                posX += this.tileSize
            }
            posX = 0;
            posY -= this.tileSize
        }

        this.drawMap(0, 0)
    }
}

var isGesturing = false

var moveCount = 0
var moveInterval = null
var refreshInterval = null

var hiddenMap = 1
var discoveryRadius = 2

var renderTemp = false
var renderRain = false
var renderBiome = true
var renderHeight = false
var renderBorders = true

var scale = 5
var tempOctaves = 2
var rainOctaves = 1
var tempPower = 1.5
var rainPower = 0.9

var water = 0
var waterCutoff = 0.2

var ajaxTempData
var ajaxRainData
var ajaxBiomeData
var ajaxLocationsData
var dirRequest

var map = new Map()

var playerDotImage = new Image()
playerDotImage.src = 'img/dot.png'
playerDotImage.srcset = 'img/dot.png 1x, img/dot-2x.png 2x, img/dot-3x.png 3x'

var whittakerColours = {
    '0' : '#272c30',
    '1' : '#93a7ac',
    '2' : '#927e30',
    '3' : '#b37c05',
    '4' : '#5b8f52',
    '5' : '#2c89a0',
    '6' : '#0a546d',
    '7' : '#c87137',
    '8' : '#97a527',
    '9' : '#075330',
    '10' : '#0000ff',
}

var redblobColours = {
    '0' : '#272c30',
    '1' : '#dddde4',
    '2' : '#c9d29b',
    '3' : '#88aa55',
    '4' : '#889977',
    '5' : '#679459',
    '6' : '#448855',
    '7' : '#d2b98b',
    '8' : '#559944',
    '9' : '#337755',
    '10' : '#0f5e9c',
}

function initialise() {
    $("#loading-spinner").css('font-size', 36)
    
    enableListeners()

    retrieveParameters()

    loadMapList()
}

function retrieveParameters() {
    dt = {
        op : 'params'
    };
    fn = function(data) {
        var json = JSON.parse(data)

        document.getElementById('map-save-check').checked = json.cookie

        $('#seed').text(json.seed)
        $('.name').text(json.name)

        hiddenMap = json.hide
        if (hiddenMap == 0) {
            document.getElementById("input-discovery-hidden").checked = false
            document.getElementById("input-discovery-visible").checked = true
            document.getElementById("input-discovery-range").disabled = true
            $('#map-discovery-counter').addClass('text-muted')
        } else {
            document.getElementById("input-discovery-hidden").checked = true
            document.getElementById("input-discovery-visible").checked = false
            document.getElementById("input-discovery-range").disabled = false
            $('#map-discovery-counter').removeClass('text-muted')
        }

        document.getElementById('map-resize-counter').innerHTML = "Map Size: " + json.size
        document.getElementById('input-resize-range').value = json.size

        discoveryRadius = json.rad
        document.getElementById('map-discovery-counter').innerHTML = "Discovery Radius: " + discoveryRadius
        document.getElementById('input-discovery-range').value = discoveryRadius

        renderTemp = !!json.temp
        document.getElementById('input-render-temp').checked = renderTemp
        renderRain = !!json.rain
        document.getElementById('input-render-rain').checked = renderRain
        renderBiome = !!json.biomes
        document.getElementById('input-render-biome').checked = renderBiome
        renderHeight = !!json.height
        document.getElementById('input-render-height').checked = renderHeight
        renderBorders = !!json.grid
        document.getElementById('input-render-borders').checked = renderBorders

        scale = json.scale
        document.getElementById('render-scale-counter').innerHTML = "Map Scale: " + scale + "x"
        document.getElementById('input-scale-range').value = scale
        tempOctaves = json.toct
        document.getElementById('render-toct-counter').innerHTML = "Height Octaves: " + tempOctaves
        document.getElementById('input-toct-range').value = tempOctaves
        rainOctaves = json.roct
        document.getElementById('render-roct-counter').innerHTML = "Rain Octaves: " + rainOctaves
        document.getElementById('input-roct-range').value = rainOctaves
        tempPower = json.tpow
        document.getElementById('render-tpow-counter').innerHTML = "Height Power: " + tempPower
        document.getElementById('input-tpow-range').value = tempPower
        rainPower = json.rpow
        document.getElementById('render-rpow-counter').innerHTML = "Rain Power: " + rainPower
        document.getElementById('input-rpow-range').value = rainPower
        
        water = json.water
        if (water == 0) {
            document.getElementById('input-render-water').checked = false
            document.getElementById("input-water-range").disabled = true
            $('#render-water-counter').addClass('text-muted')
        } else {
            document.getElementById('input-render-water').checked = true
            document.getElementById("input-water-range").disabled = false
            $('#render-water-counter').removeClass('text-muted')
        }
        waterCutoff = json.wcut
        document.getElementById('render-water-counter').innerHTML = "Water Level: " + waterCutoff
        document.getElementById('input-water-range').value = waterCutoff

        displayToken(json.token)

        map = new Map(json.size, renderTemp, renderRain, renderBiome, renderHeight, renderBorders)
        moveRequest(0)
    };
    $.post('ajax.php', dt, fn)
}

function setCookie() {
    var cookie = document.getElementById('map-save-check').checked
    dt = {
        op : 'cookie',
        cookie : cookie | 0
    };
    fn = function(data) {

    }
    $.post('ajax.php', dt, fn)
}

function loadMapList() {
    var id = $('#load-map-token').val();
    dt = {
        op : 'load',
        id : id
    };
    fn = function(data) {
        var json = JSON.parse(data)

        var mapSelect = $('#load-map-select')
        mapSelect.empty()
        $(json.list).each(function() {
            mapSelect.append('<option value="' + this.val +  '">' + this.text + '</option>')
        })

    };
    $.post('ajax.php', dt, fn)
}

function newMap() {
    dt = {
        op : 'create',
        seed : parseInt($('#new-map-seed').val()),
        name : $('#new-map-name').val()
    };
    fn = function(data) {
        retrieveParameters()
        loadMapList()
    };
    $.post('ajax.php', dt, fn)
}

function deleteMap() {
    dt = {
        op : 'delmap',
        id : $('#load-map-token').val(),
        map : $('#load-map-select').val()
    };
    fn = function(data) {
        retrieveParameters()
        loadMapList()
    };
    $.post('ajax.php', dt, fn)
}

function changeId() {
    dt = {
        op : 'change',
        id : $('#load-map-token').val(),
        map : $('#load-map-select').val()
    };
    fn = function(data) {
        retrieveParameters()
        loadMapList()
    };
    $.post('ajax.php', dt, fn)
}

function randomSeed() {
    $('#new-map-seed').val(Math.floor(Math.random() * (Number.MAX_SAFE_INTEGER - Number.MIN_SAFE_INTEGER + 1) ) + Number.MIN_SAFE_INTEGER);
}

function enableListeners() {
    var mc = new Hammer(map.canvas);
    mc.get('swipe').set({ direction: Hammer.DIRECTION_ALL });
    mc.on('swipeleft swiperight swipeup swipedown', function(ev) {
        if (isGesturing) return
        isGesturing = true;
        switch (ev.type) {
            case 'swipeleft':
                moveRequest(37)
                break;
            case 'swipeup':
                moveRequest(38)
                break;
            case 'swiperight':
                moveRequest(39)
                break;
            case 'swipedown':
                moveRequest(40)
                break;
            default:
                moveRequest(0)
                break;
        }
    });

    $('.input-giant-map').click(function() {
        document.getElementById("input-resize-range").value = document.getElementById("input-resize-range").max
        document.getElementById("map-resize-counter").innerHTML = "Map Size: 201"
        document.getElementById("input-render-borders").checked = false
        document.getElementById("input-discovery-range").disabled = true
        document.getElementById("input-discovery-visible").checked = true
        document.getElementById("input-discovery-hidden").checked = false
        $('#map-discovery-counter').addClass('text-muted')
        renderBorders = false
        hiddenMap = 0
        map = new Map(201, renderTemp, renderRain, renderBiome, renderHeight, renderBorders)
        moveRequest(0)
    });

    $('.input-clear-tiles').click(function() {
        dt = {
            op : 'clear'
        };
        fn = function(data) {
            moveRequest(0)
        };
        $.post('ajax.php', dt, fn)
    });

    document.getElementById("input-resize-range").oninput = function() { document.getElementById("map-resize-counter").innerHTML = "Map Size: " + this.value }
    document.getElementById("input-resize-range").onchange = function() {
        map = new Map(parseInt(this.value), renderTemp, renderRain, renderBiome, renderHeight, renderBorders)
        moveRequest(0)
    }

    document.getElementById("input-discovery-range").oninput = function() { document.getElementById("map-discovery-counter").innerHTML = "Discovery Radius: " + this.value }
    document.getElementById("input-discovery-range").onchange = function() {
        discoveryRadius = parseInt(this.value)
        moveRequest(0)
    }

    document.getElementById("input-scale-range").oninput = function() { document.getElementById("render-scale-counter").innerHTML = "Map Scale: " + this.value + "x" }
    document.getElementById("input-scale-range").onchange = function() {
        scale = parseInt(this.value)
        moveRequest(0)
    }
    document.getElementById("input-toct-range").oninput = function() { document.getElementById("render-toct-counter").innerHTML = "Height Octaves: " + this.value }
    document.getElementById("input-toct-range").onchange = function() {
        tempOctaves = parseInt(this.value)
        moveRequest(0)
    }
    document.getElementById("input-roct-range").oninput = function() { document.getElementById("render-roct-counter").innerHTML = "Rain Octaves: " + this.value }
    document.getElementById("input-roct-range").onchange = function() {
        rainOctaves = parseInt(this.value)
        moveRequest(0)
    }
    document.getElementById("input-tpow-range").oninput = function() { document.getElementById("render-tpow-counter").innerHTML = "Height Power: " + this.value }
    document.getElementById("input-tpow-range").onchange = function() {
        tempPower = parseFloat(this.value)
        moveRequest(0)
    }
    document.getElementById("input-rpow-range").oninput = function() { document.getElementById("render-rpow-counter").innerHTML = "Rain Power: " + this.value }
    document.getElementById("input-rpow-range").onchange = function() {
        rainPower = parseFloat(this.value)
        moveRequest(0)
    }

    document.getElementById("input-water-range").oninput = function() { document.getElementById("render-water-counter").innerHTML = "Water Level: " + this.value }
    document.getElementById("input-water-range").onchange = function() {
        waterCutoff = parseFloat(this.value)
        moveRequest(0)
    }

    document.getElementById

    var visibleRadio = document.getElementById("input-discovery-visible")
    var hiddenRadio = document.getElementById("input-discovery-hidden")
    visibleRadio.onclick = function() {
        hiddenRadio.checked = false
        hiddenMap = 0
        $('#map-discovery-counter').addClass('text-muted')
        document.getElementById("input-discovery-range").disabled = true
        moveRequest(0)
    }
    hiddenRadio.onclick = function() {
        visibleRadio.checked = false
        hiddenMap = 1
        $('#map-discovery-counter').removeClass('text-muted')
        document.getElementById("input-discovery-range").disabled = false
        moveRequest(0)
    }
}

function updateMapLayers() {
    var mapLength = map.mapLength
    renderTemp = document.getElementById("input-render-temp").checked
    renderRain = document.getElementById("input-render-rain").checked
    renderBiome = document.getElementById("input-render-biome").checked
    renderHeight = document.getElementById("input-render-height").checked
    renderBorders = document.getElementById("input-render-borders").checked
    if (document.getElementById("input-render-water").checked) {
        water = 1
        $('#render-water-counter').removeClass('text-muted')
        document.getElementById("input-water-range").disabled = false
    } else {
        water = 0
        $('#render-water-counter').addClass('text-muted')
        document.getElementById("input-water-range").disabled = true
    }
    map = new Map(mapLength, renderTemp, renderRain, renderBiome, renderHeight, renderBorders)
    moveRequest(0)
}

function move(dir) {
    if (moveCount >= map.tileSize) {
        clearInterval(moveInterval)
        moveCount = 0
        moveInterval = null;
        return
    }
    moveCount += 1;

    switch (dir) {
       case 37:
            map.drawMap(moveCount, 0) //l
          break;
       case 38:
            map.drawMap(0, moveCount) //r
          break;
       case 39:
            map.drawMap(0 - moveCount, 0) //u
          break;
       case 40:
            map.drawMap(0, 0 - moveCount) //d
          break;
        default:
            break;
    }
}

function refresh() {
    if (moveInterval == null && ajaxTempData && ajaxRainData && ajaxBiomeData && ajaxLocationsData) {
        clearInterval(refreshInterval)

        map.tempArray = ajaxTempData
        map.rainArray = ajaxRainData
        map.biomeArray = ajaxBiomeData
        map.locationsArray = ajaxLocationsData;

        map.renderMap(dirRequest)

        refreshInterval = null
        isGesturing = false

        $("#loading-spinner").hide();
    }
}

document.onkeydown = function(event) {
    if (event.keyCode < 37 || event.keyCode > 40) return
    event.preventDefault()

    moveRequest(event.keyCode)
};

function moveRequest(dir) {
    if (moveInterval != null) return
    if (refreshInterval != null) return

    var intervalSpeed = 1000 / map.tileSize * 0.8
    var isArrow = false;

    ajaxTempData = null;
    ajaxRainData = null;
    ajaxBiomeData = null;
    ajaxLocationsData = null;
    dirRequest = dir;

    moveInterval = setInterval(move, intervalSpeed, dir)
    refreshInterval = setInterval(refresh, 20)

    dt = {
        op : 'move',
        size : map.mapLength,
        dir : dir,
        hide : hiddenMap,
        rad : discoveryRadius,
        scale : scale,
        toct : tempOctaves,
        roct : rainOctaves,
        tpow : tempPower,
        rpow : rainPower,
        temp : renderTemp | 0,
        rain : renderRain | 0,
        biomes : renderBiome | 0,
        height : renderHeight | 0,
        grid : renderBorders | 0,
        water : water,
        wcut : waterCutoff
    };
    fn = function(data) {
        json = JSON.parse(data)
        ajaxTempData = json.temp
        ajaxRainData = json.rain
        ajaxBiomeData = json.biome
        ajaxLocationsData = []
    };
    $.post('ajax.php', dt, fn)

    $("#loading-spinner").show();
}

function displayToken(token) {
    $('.token').text(token)
    $('#new-map-token').attr('value', token)
    $('#load-map-token').attr('placeholder', token)
}