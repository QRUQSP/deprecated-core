//
// Device: generic
// Engine: webkit
// Browsers: chrome, safari
// Platforms: windows, mac, linux
//
Q.xmlHttpCreate = function() {
    var req = null;
    try{
        req=new XMLHttpRequest();
    }catch(e){
        req=null;
    };
    return req; 
};
