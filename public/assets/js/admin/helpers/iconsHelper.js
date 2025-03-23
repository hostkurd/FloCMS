class IconHelper{
    constructor(options){
        this.data = [];
        //Containers
        this.iconContainer = document.getElementById(options.container);
        this.iconBox = document.getElementById(options.iconBox);
        // Holders
        this.colorHolder = document.getElementById(options.colorHolder);
        this.pathHolder = document.getElementById(options.pathHolder);
        this.iconHolder = document.getElementById(options.iconHolder);
        this.typeHolder = document.getElementById(options.typeHolder);
        this.placeHolder = document.getElementById(options.placeHolder);
        //var name
        this.varName = options.variableName;

        this.getIconList().then(icons=>{
            this.data = icons;
            this.generateIcons('green');
            this.svgInjectAll();
        });
    }

    // Get Icon Data from directory
    async getIconList() {
        const response = await fetch(site_url+'/json/json-data.php?action=list-icons&type=lineal');
        const data = await response.json();
        return data;
    }

    showIconDialog(d){
        let width = 700;
        let height = 500;
        let margin = 5;
        width = screen.width < 700 ? screen.width : 700;

        // decrease 10 for
        var left = (screen.width - width) / 2;
        var top = (screen.height - height) / 2;

        this.iconContainer.style.width = width - (margin * 2) +'px';
        this.iconContainer.style.height = height+'px';
        this.iconContainer.style.left = left+'px';
        this.iconContainer.style.top = top+'px';
        this.iconContainer.style.margin = '0 ' + margin+'px';

        this.iconContainer.style.display = 'block';

    }

    generateIcons(color){
        this.data.forEach(element => {
            this.iconBox.appendChild(this.populateImg(element,'text-'+ color))
        });
    }

    populateImg(element, colorClass){
        var img = document.createElement('img');
        img.src = site_url+'/assets/img/icons/'+ element.type + '/' + element.file;
        img.classList.add('svg-inject');
        img.classList.add('icon-svg');
        if(element.type == 'solid'){
            img.classList.add('solid');
        }
        img.classList.add(colorClass);

    
        var a = document.createElement('a');
        a.classList.add('icon-link')
        a.setAttribute("onclick",this.varName+'.getIconData(this)');
        a.title = element.file.replace('.svg','');
        a.setAttribute("data-icon",element.file);
        a.setAttribute("data-type",element.type);
        a.appendChild(img);
        return a;
    }

    getIconData(d){
        let icon = d.getAttribute('data-icon'); 
        let type = d.getAttribute('data-type'); 

        this.pathHolder.value = icon;
        this.typeHolder.value = type;
        this.iconContainer.style.display = 'none';

        var img = document.createElement('img');
        img.src = iconsPath + '/' + type + '/' + icon;
        img.classList.add('svg-inject');
        if(type == 'solid'){img.classList.add('solid');}

        var a = document.createElement('a');
        a.appendChild(document.createTextNode('Change Icon'));
        a.setAttribute("onclick",this.varName+'.showIconDialog(this)');

        this.iconHolder.innerHTML = '';
        this.iconHolder.style.display = 'flex';
        this.placeHolder.style.display = 'none';

        this.iconHolder.appendChild(img);
        this.iconHolder.appendChild(a);
        this.svgInject(img);
        
    }

    changeColor(d){
        this.iconBox.innerHTML = '';
        this.generateIcons(d.value);
        this.svgInjectAll();
        this.colorHolder.value = d.value;
        this.iconHolder.classList = '';
        this.iconHolder.classList.add('text-'+d.value);
    }

    svgInjectAll(){
        SVGInject(document.querySelectorAll('img.svg-inject'), {
        useCache: true
        });
    }

    svgInject(element){
        SVGInject(element, {
            useCache: true
        });
    }

    closeIconDialog(){
        this.iconContainer.style.display = 'none';
    }

    loadCurrentData(icon, type, color){
        this.iconHolder.innerHTML = '';

        var img = document.createElement('img');
        img.src = iconsPath + '/' + type + '/' + icon;
        img.classList.add('svg-inject');
        //img.classList.add('text-'+color);
        if(type == 'solid'){img.classList.add('solid');}

        var a = document.createElement('a');
        a.appendChild(document.createTextNode('Change Icon'));
        a.setAttribute("onclick",this.varName+'.showIconDialog(this)');
        a.classList.add('text-'+color);
        
        this.iconHolder.appendChild(img);
        this.iconHolder.appendChild(a);
        this.iconHolder.classList = '';
        this.iconHolder.classList.add('text-' + color);
        this.svgInject(img);
        

        this.iconHolder.style.display = 'flex';
        this.placeHolder.style.display = 'none';

    }
}

/**
 * SVGInject
 * Replaces an img element with an inline SVG so you can apply colors to your SVGs
*/
SVGInject.setOptions({
    onFail: function(img, svg) {
    img.classList.remove('svg-inject');
    }
});
document.addEventListener('DOMContentLoaded', function() {
    SVGInject(document.querySelectorAll('img.svg-inject'), {
        useCache: true
    });
});

