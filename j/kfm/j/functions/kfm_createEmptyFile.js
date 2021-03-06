window.kfm_createEmptyFile=function(filename,msg){
  kfm_selectNone(); // Backspace problems etc.
	if(!filename || filename.toString()!==filename){
		filename='';
		msg='';
	}
	kfm_prompt(kfm.lang.WhatFilenameToCreateAs+msg,filename,function(filename){
		if(!filename)return;
		if(filename.indexOf('/')>-1){
			msg=kfm.lang.NoForwardslash;
      		kfm_createEmptyFile(filename,msg);
		}else{
		  if(kfm_isFileInCWD(filename)){
			  kfm.confirm(kfm.lang.AskIfOverwrite(filename),function(){
		      x_kfm_createEmptyFile(kfm_cwd_id,filename,kfm_refreshFiles);
        });
      }else{
		    x_kfm_createEmptyFile(kfm_cwd_id,filename,kfm_refreshFiles);
      }
		}
	});
}
