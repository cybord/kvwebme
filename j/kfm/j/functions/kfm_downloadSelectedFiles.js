window.kfm_downloadSelectedFiles=function(id){
	var wrapper=document.getElementById('kfm_download_wrapper');
	if(!wrapper){
		wrapper=document.createElement('kfm_download_wrapper');
		wrapper.style.display='none';
		kfm.addEl(document.body,wrapper);
	}
	wrapper.innerHTML='';
	if(id)kfm_downloadSelectedFiles_addIframe(wrapper,id);
	else for(var i=0;i<selectedFiles.length;++i)kfm_downloadSelectedFiles_addIframe(wrapper,selectedFiles[i]);
}
