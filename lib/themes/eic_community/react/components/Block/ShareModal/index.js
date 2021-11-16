import React, {useState} from 'react'
import svg from '../../../svg/svg'
import axios from "axios"
import notify from "../../../Services/NotificationHelper"

const ShareModal = ({groups, endpoint, settings}) => {

  const [selectValue, setSelectValue] = useState({group: '00', message: ''})
  const [showModal, setShowModal] = useState(false)
  const submitData = () => {
    axios.post(endpoint, selectValue)
      .then(response => {
        setShowModal(false)
        if(response.data && response.data.status) {
          notify(response.data.message)
        }else {
          notify(response.data.message, 'danger')
        }
      })
      .catch(function (error) {
        notify('An error occurred', 'danger')
      })

  }

  const handleChange = e => {
    setSelectValue({
      ...selectValue,
    [e.target.name]: e.target.value
    })
  }

  return (
    <>
      <span className="flag flag-bookmark-content js-flag-bookmark-content-2 action-flag">
        <a onClick={() => setShowModal(!showModal)} title="" href="#">Share with another group</a>
      </span>
      {
        showModal &&
        <div className="ecl-viewport__modal" >
          <div className="ecl-viewport__modal__content">
            <span onClick={() => setShowModal(false)} className="ecl-link ecl-link--standalone ecl-link--icon ecl-link--icon-after ecl-activity-stream__modal__call-to-action ecl-link--button ecl-link--button-ghost">
              <span className="ecl-link__label">Close</span>
              &nbsp;
              <div dangerouslySetInnerHTML={{__html: svg("clear", "ecl-icon ecl-icon--2xs ecl-link__icon")}} />
            </span>
            <h3 className="ecl-activity-stream__modal__title">Share this content to a group</h3>

            <div className="ecl-form">
              <div className="ecl-form-group">
                <label htmlFor="modal-groups" className="ecl-form-label">
                  My groups
                </label>
                <div className="ecl-select__container ecl-select__container--full">
                  <select className="ecl-select" id="modal-groups" name="group" onChange={handleChange} value={selectValue.group}>
                    <option value="00" disabled>- Select a group -</option>
                    {groups.map((group) => <option value={group.id} key={group.id}>{group.name}</option>)}
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
                <textarea onChange={handleChange} className="ecl-text-area ecl-text-area--full" rows="4" name="message" value={selectValue.message} required />
              </div>
            </div>

            <div className="ecl-inline-actions">
              <div className="ecl-inline-actions__items">
                <div className="ecl-inline-actions__item">
                  <a href="#" onClick={submitData} className="ecl-link ecl-link--default ecl-link--button ecl-link--button-primary">
                    Share
                  </a>
                </div>
                <div className="ecl-inline-actions__item">
                  <button onClick={() => setShowModal(false)}
                        className="ecl-link ecl-link--default ecl-link--button ecl-link--button-secondary">Cancel
                  </button>
                </div>
              </div>
            </div>
          </div>
        </div>
      }

    </>
  )
}

export default ShareModal;
