import React from "react";
import GetAppIcon from '@material-ui/icons/GetApp';
import ImageIcon from '@material-ui/icons/Image';

const FileInfo = ({file, position, length}) => {
  return (
    <>
      <div className={"ecl-gallery__data"}>
        <p className={"ecl-gallery__data__title"}>{file.name} ({position}/{length})</p>
        <div className={"ecl-gallery__data__actions"}>
          <ul>
            <li> <GetAppIcon /> {file.stats[0].value} {file.stats[0].label}</li>
            <li> <ImageIcon /> {file.mime_type} </li>
            <li> {file.stats[1].value} </li>
          </ul>
          <a className={"ecl-link ecl-link--standalone ecl-link--button ecl-link--button-primary ecl-link--icon ecl-link--svg"} href={file.path} target="_blank" > <GetAppIcon /> Download </a>
        </div>
      </div>
      <p className={"ecl-gallery__data__description"}>{file.description}</p>
    </>
  )
}

export default React.memo(FileInfo)
