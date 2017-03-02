var cont;
var circle=new Function();
if (document.createElementNS) {
    svgNS = "http://www.w3.org/2000/svg";
    svg = document.createElementNS(svgNS, "svg");
    SVG = (svg.x != null);
    createSVGVML = function(o, antialias) {
        cont = document.createElementNS(svgNS, "svg");
        o.appendChild(cont);
        svgAntialias = antialias;
    }
    circle = function(diam, color) {
        var o = document.createElementNS(svgNS, "circle");
        o.setAttribute("shape-rendering", false);
        o.setAttribute("stroke-width", "2px");
        o.setAttribute("stroke-color", color);
        o.setAttribute("r", Math.round(diam / 2));
        o.setAttribute("cx", (diam / 2+2)+"px");
        o.setAttribute("cy", (diam / 2+2)+"px");
        o.setAttribute("stroke", color);
        o.setAttribute("fill", "none");
        o.style.cursor = "pointer";
        cont.appendChild(o);
   }
}else if(document.createStyleSheet){
    
    createSVGVML = function(o, antialias) {
        document.namespaces.add("v", "urn:schemas-microsoft-com:vml");
        var style = document.createStyleSheet();
        style.addRule('v\\:*', "behavior: url(#default#VML);");
        style.addRule('v\\:*', "antialias: "+antialias+";");
        cont = o;
    }
    circle = function (diam, filled) {
        var o = document.createElement("v:oval");
        o.style.position = "absolute";
        o.style.cursor = "pointer";
        o.strokeweight = 2;
        o.filled = filled;
        o.style.width = diam + "px";
        o.style.height = diam + "px";
        cont.appendChild(o);
        try{
         return o;
        }finally{
         o=null;
        }
    }
}
createSVGVML(document.getElementById("svg"));

circle.prototype.getTime = function() {
    //取得当前时间   
    var now= new Date();   
    var year=now.getFullYear();   
    var month=now.getMonth()+1;   
    var day=now.getDate();    
    var nowdate=year+"."+month+"."+day; 
    document.write("<div style='left:340px;top:110px;position:absolute;'>上海</div>");
    document.write("<div style='left:325px;top:150px;position:absolute;'>"+nowdate+"</div>");
    document.write("<div style='left:320px;top:190px;position:absolute;'>田园速递01</div>");
}
new circle(120,"#000000").getTime();