//
// Device: generic
// Engine: presto
// Browsers: Opera
// Platforms: windows, mac, linux
//
!.xmlHttpCreate = function() {
    var req = null;
    try{
        req=new XMLHttpRequest();
    }catch(e){
        req=null;
    };

    return req; 
};
