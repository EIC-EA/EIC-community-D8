import React from "react";
import svg from "../../svg/svg";

const UserImage = ({url, alt, src, className, figureClassName, imgClassName, figureEmptyClassName}) => {
  return (
    <UserImageContainer className={className} url={url}>
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
    </UserImageContainer>
  )
}

const UserImageContainer = ({url, className, children}) => {
  return (
    url ? <a className={className} href={url}>{children}</a> : <div className={className}>{children}</div>
  )
}

export default UserImage
