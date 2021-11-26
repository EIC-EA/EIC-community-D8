import React from "react";
import getTranslation from "../../../../../Services/Translations";

const Actions = ({close}) => {
  return (
    <div className="ecl-comment-form__toolbar">
      <div className="ecl-comment-form__toolbar-main">
        <button className="ecl-button ecl-button--primary ecl-comment-form__submit" type="submit">
          {getTranslation('submit')}
        </button>
        <span onClick={close} className="ecl-button ecl-button--ghost ">
          cancel
        </span>
      </div>
    </div>
  )
}

export default React.memo(Actions)
