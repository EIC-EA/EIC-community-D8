import React, {useCallback, useEffect, useState} from "react";
import ReactDOM from "react-dom";
import svg from "../../../../svg/svg";
import axios from "axios"
import WysiswigEditor from "../../CommentsDiscussion/Partials/components/WysiswigEditor"
import EntityTree from "../../../Field/EntityTree/index"

const ShareModal = ({
  groupsUrl,
  setShowModal,
  treeWidgetSettings,
  treeWidgetTranslations,
  endpoint,
  canRecommendExternalUsers,
  title,
  description,
  successTitle,
  successDescription
}) => {
  const [groups, setGroups] = useState({})


  const [formValues, setFormValues] = useState({
    users: [],
    external_emails: '',
    message: ''
  })  
  
  const setFormValuesByKey = (key, value) => {
    if(key === 'users') {
      const users = value.map(user => {
        return user.tid
      })

      setFormValues({...formValues, ['users']: users})
      return
    }
    setFormValues({...formValues, [key]: value})
  }

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

  const [loading, setLoading] = useState(false)
  const [isSent, setIsSent] = useState(false)

  const handleSubmit = async (e) => {
    e.preventDefault()
    try {
      setLoading(true)
      const { data } = await axios.post(endpoint, formValues)
      setIsSent(true)
    } catch (error) {
      console.error(error)
    } finally {
      setLoading(false)
    }
  }

  return ReactDOM.createPortal((
    <div className="ecl-viewport__modal" >
      <div className="ecl-viewport__modal__content">
        <span onClick={() => setShowModal(false)} className="ecl-link ecl-link--standalone ecl-link--icon ecl-link--icon-after ecl-activity-stream__modal__call-to-action ecl-link--button ecl-link--button-ghost">
          <span className="ecl-link__label">Close</span>
          &nbsp;
          <div dangerouslySetInnerHTML={{__html: svg("clear", "ecl-icon ecl-icon--2xs ecl-link__icon")}} />
        </span>
        {!isSent
          ? (
            <>
              <h3 className="ecl-activity-stream__modal__title">{title}</h3>
              <p>{description}</p>

              <form className="ecl-form" onSubmit={handleSubmit}>

                <div className="ecl-form-group">
                  <EntityTree
                    url={treeWidgetSettings.endpoint}
                    urlSearch={treeWidgetSettings.endpoint}
                    urlChildren={treeWidgetSettings.endpoint}
                    targetEntity={treeWidgetSettings.target_entity}
                    searchSpecificUsers={1}
                    // selectedTerms={JSON.stringify(taggedUsers)}
                    matchLimit={0}
                    length={25}
                    disableTop={0}
                    loadAll={1}
                    ignoreCurrentUser={1}
                    isRequired={0}
                    group={treeWidgetSettings.current_group}
                    translations={treeWidgetTranslations}
                    onChange={(value) => {setFormValuesByKey('users', value)}}
                    // addElementsToExternal={updateTaggedUsers}
                  />
                </div>
                {canRecommendExternalUsers && (
                  <div className="ecl-form-group">
                    <label htmlFor="modal-groups" className="ecl-form-label">
                    Select external users
                    </label>
                    <textarea
                      className="ecl-text-area ecl-text-area--full"
                      rows="3"
                      placeholder="You can copy/paste multiple emails, enter one email per line."
                      onChange={(e) => setFormValuesByKey("external_emails", e.target.value)}
                    >
                    </textarea>
                  </div>
                )}

                <div className="ecl-form-group">
                  <label htmlFor="modal-groups" className="ecl-form-label">
                  Your message
                  </label>
                  <WysiswigEditor
                    // value={(this.props.commentText || this.props.commentTextReply) || ''}
                    onChange={(value) => setFormValuesByKey("message", value)}
                    id="ecl-comment-form-reply"
                    required={true}
                    placeholder={'Your message here'}
                  />
                </div>
                <div className="ecl-inline-actions">
                  <div className="ecl-inline-actions__items">
                    <div className="ecl-inline-actions__item">
                      <button type="submit" className="ecl-link ecl-link--default ecl-link--button ecl-link--button-primary">
                        Recommend
                      </button>
                    </div>
                    <div className="ecl-inline-actions__item">
                      <button
                      disabled={loading}
                      onClick={(e) => {
                        e.preventDefault()
                        setShowModal(false)
                      }}
                      className="ecl-link ecl-link--default ecl-link--button ecl-link--button-secondary"
                      >
                        Cancel
                      </button>
                    </div>
                  </div>
                </div>

              </form>
            </>
          )
          : (
            <>
              <h3 className="ecl-activity-stream__modal__title">{successTitle}</h3>
              <div dangerouslySetInnerHTML={{__html: successDescription}}></div>
            </>
          )
        }
      </div>
    </div>
  ), document.querySelector("body"))
}

export default ShareModal
