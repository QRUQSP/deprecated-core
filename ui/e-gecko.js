//
// Device: generic
// Engine: gecko
// Browsers: firefox
// Platforms: windows, mac, linux
//
Q.xmlHttpCreate = function() {
    var req=null;
    try{
        req=new XMLHttpRequest();
    }catch(e){
        req=null;
    };

    return req; 
};
