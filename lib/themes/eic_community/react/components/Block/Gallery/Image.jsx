import React from "react";
import FileInfo from "./FileInfo";


const Gallery = ({file}) => {
  return (
    <>
      <img style={{width: '100%'}} src={file.image.src} alt={file.image.src}/>
      <FileInfo
        file={file}
      />
    </>
  )
};


export default Gallery;
