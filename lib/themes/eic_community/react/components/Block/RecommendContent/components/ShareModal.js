import React, {useCallback, useEffect, useState} from "react";
import ReactDOM from "react-dom";
import svg from "../../../../svg/svg";
import axios from "axios"
import WysiswigEditor from "../../CommentsDiscussion/Partials/components/WysiswigEditor"
import EntityTree from "../../../Field/EntityTree/index"

const ShareModal = ({groupsUrl, setShowModal}) => {

  const [groups, setGroups] = useState({})

  const handleKeypress = useCallback((e) => {
    if(e.key === "Escape") {
      setShowModal(false)
    }
  }, [])

  useEffect(() => {
    axios.get(groupsUrl).then(response => {
      if(response.data) {
        setGroups(response.data)
      }
    }).catch( error => {
      console.error(error);
    })

    document.addEventListener("keydown", handleKeypress )

    return () => {
      document.removeEventListener("keydown", handleKeypress )
    }
  }, [])

  return ReactDOM.createPortal((
    <div className="ecl-viewport__modal" >
      <div className="ecl-viewport__modal__content">
        <span onClick={() => setShowModal(false)} className="ecl-link ecl-link--standalone ecl-link--icon ecl-link--icon-after ecl-activity-stream__modal__call-to-action ecl-link--button ecl-link--button-ghost">
          <span className="ecl-link__label">Close</span>
          &nbsp;
          <div dangerouslySetInnerHTML={{__html: svg("clear", "ecl-icon ecl-icon--2xs ecl-link__icon")}} />
        </span>

        <h3 className="ecl-activity-stream__modal__title">Recommend this content</h3>
        <p>
        Select exisiting members or people outside of the platform you wish to recommend this content to. Those will be presented to you as long as they have the permission to view the content you want to share.
        </p>

        <form className="ecl-form">

          <div className="ecl-form-group">
            <EntityTree
              url={element.dataset.termsUrl}
              urlSearch={element.dataset.termsUrlSearch}
              urlChildren={element.dataset.termsUrlChildren}
              createTermUrl={element.dataset.createTermUrl}
              selectedTerms={element.dataset.selectedTerms}
              targetEntity={element.dataset.targetEntity}
              targetBundle={element.dataset.targetBundle}
              matchLimit={element.dataset.matchLimit ? parseInt(element.dataset.matchLimit) : 0}
              length={element.dataset.itemsToLoad ? parseInt(element.dataset.itemsToLoad) : 25}
              disableTop={parseInt(element.dataset.disableTop)}
              loadAll={parseInt(element.dataset.loadAll)}
              ignoreCurrentUser={parseInt(element.dataset.ignoreCurrentUser)}
              isRequired={parseInt(element.dataset.isRequired)}
              drupalInput={element}
              translations={JSON.parse(element.dataset.translations)}
              group={element.dataset.groupId}
              searchSpecificUsers={parseInt(element.dataset.searchSpecificUsers)}
              canCreateTag={false}
            />
          </div>

          <div className="ecl-form-group">
            <label htmlFor="modal-groups" className="ecl-form-label">
            Select external users
            </label>
            <textarea placeholder="You can copy/paste multiple emails, enter one email per line.">

            </textarea>
          </div>

          <div className="ecl-form-group">
            <label htmlFor="modal-groups" className="ecl-form-label">
            Your message
            </label>
            <WysiswigEditor
              // value={(this.props.commentText || this.props.commentTextReply) || ''}
              // onChange={this.handleChange}
              id="ecl-comment-form-reply"
              required={true}
              placeholder={'Your message here'}
            />
          </div>

        </form>

        {/* <form onSubmit={handleSubmit} className="ecl-form">
          <div className="ecl-form-group">
            <label htmlFor="modal-groups" className="ecl-form-label">
              My groups
            </label>
            <div className="ecl-select__container ecl-select__container--full">
              <select className="ecl-select" id="modal-groups" name="group" onChange={handleChange} value={selectValue.group}>
                <option value="00" disabled>- Select a group -</option>
                {Object.keys(groups).map((key) => {
                  return (
                      <optgroup label={key} key={key}>
                        {groups[key].map(entity => <option value={entity.id} key={entity.id}>{entity.label}</option>)}
                      </optgroup>
                  )})}
              </select>
              <div className="ecl-select__icon" dangerouslySetInnerHTML={{__html: svg("arrow-down", "ecl-icon ecl-icon--s ecl-icon--rotate-180 ecl-select__icon-shape")}} />
            </div>
          </div>

          <div className="ecl-form-group">
            <label className="ecl-form-label">
              Message
              <span className="ecl-form-label__required">*</span>
            </label>
            {settings.translations && settings.translations.maximum_character &&
            <div className="ecl-help-block">{settings.translations.maximum_character}</div>
            }
            <textarea onChange={handleChange} className="ecl-text-area ecl-text-area--full" rows="4" name="message" maxLength={300} value={selectValue.message} required />
          </div>

          <div className="ecl-inline-actions">
            <div className="ecl-inline-actions__items">
              <div className="ecl-inline-actions__item">
                <button type="submit" className="ecl-link ecl-link--default ecl-link--button ecl-link--button-primary">
                  Share
                </button>
              </div>
              <div className="ecl-inline-actions__item">
                <button onClick={(e) => {
                  e.preventDefault()
                  setShowModal(false)
                }}
                        className="ecl-link ecl-link--default ecl-link--button ecl-link--button-secondary">Cancel
                </button>
              </div>
            </div>
          </div>
        </form> */}
      </div>
    </div>
  ), document.querySelector("body"))
}

export default ShareModal
