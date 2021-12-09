import Popup from "reactjs-popup";
import svg from "../../../../../svg/svg";
import React from "react";
import EntityTree from "../../../../Field/EntityTree";
import PopupLayout from "./Layout";
import Actions from "./Actions";

const TaggedUsersModal = ({ settings, taggedUsers, updateTaggedUsers }) => {

  const closeModal = (close) => {
    updateTaggedUsers([])
    close()
  }

  return (
    <div className="ecl-comment-form__toolbar-aside">
      <button
        className="ecl-button ecl-button--ghost ecl-comment-form__attachment ecl-button--as-form-option"
        type="button"
      >
        <span className="ecl-button__container">
          <span className="ecl-button__label" data-ecl-label="true">
            Tag user
          </span>
          <Popup
            trigger={
              <span
                style={{ cursor: "pointer" }}
                dangerouslySetInnerHTML={{
                  __html: svg(
                    "user",
                    "ecl-icon ecl-icon--s ecl-timestamp__icon"
                  ),
                }}
              />
            }
            title={settings.translations.modal_invite_users_title}
            modal
            nested
            lockScroll={true}
          >
            {(close) => (
              <PopupLayout close={() => closeModal(close)} title={settings.translations.modal_invite_users_title}>
                <div className="content">
                  <div className="ecl-comment-overview__form-wrapper">
                    <div className="ecl-comment-form ">
                      <div className="ecl-comment-form__content">
                        <div className="ecl-comment-form__main">
                          <h3>{settings.translations.select_users}</h3>
                          <EntityTree
                            url={settings.users_url}
                            urlSearch={settings.users_url_search}
                            urlChildren={settings.users_url}
                            targetEntity={"user"}
                            searchSpecificUsers={1}
                            selectedTerms={JSON.stringify(taggedUsers)}
                            matchLimit={0}
                            length={25}
                            disableTop={0}
                            loadAll={1}
                            ignoreCurrentUser={0}
                            isRequired={0}
                            translations={settings.translations}
                            addElementsToExternal={updateTaggedUsers}
                          />
                        </div>
                      </div>
                    </div>
                  </div>
                </div>
                <Actions handleSubmit={close} submitLabel={'Confirm'} close={() => closeModal(close)} />
              </PopupLayout>
            )}
          </Popup>
        </span>
      </button>
    </div>
  );
};

export default TaggedUsersModal;
