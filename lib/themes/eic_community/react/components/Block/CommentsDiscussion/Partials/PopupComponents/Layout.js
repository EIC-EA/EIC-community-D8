import React from "react";
import svg from "../../../../../svg/svg";

const PopupLayout = ({ title, close, children }) => {
  return (
    <>
      <span
        onClick={close}
        className="ecl-link ecl-link--standalone ecl-link--icon ecl-link--icon-after ecl-link--button-ghost ecl-modal__close"
      >
        <span className="ecl-link__label">Close</span>
        &nbsp;
        <div
          dangerouslySetInnerHTML={{
            __html: svg("clear", "ecl-icon ecl-icon--2xs ecl-link__icon"),
          }}
        />
      </span>
      <div className="ecl-modal__wrapper">
        <h3 className="ecl-modal__title">{title}</h3>
        {children}
      </div>
    </>
  );
};

export default React.memo(PopupLayout);
