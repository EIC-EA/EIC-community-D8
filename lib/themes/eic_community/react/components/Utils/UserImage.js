import React from "react";
import svg from "../../svg/svg";

const UserImage = ({url, alt, src, className, figureClassName, imgClassName, figureEmptyClassName}) => {

  console.log(src)

  return (
    <a className={className} href={url}>
      {src
        ?
        <figure className={figureClassName}>
          <img
            className={imgClassName}
            src={src}
            alt={`Avatar of ${alt}`}
          />
        </figure>
        :
        <figure
          className={figureEmptyClassName}
          dangerouslySetInnerHTML={{__html: svg('user', 'ecl-icon--m')}}
        />
      }
    </a>
  )
}

export default UserImage
