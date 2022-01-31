import React, {useState} from 'react'
import svg from '../../../svg/svg'
import axios from "axios"
import notify from "../../../Services/NotificationHelper"
import ShareModal from "./components/ShareModal";

const ShareElement = ({groups, endpoint, settings}) => {

  const {translations} = settings
  const [selectValue, setSelectValue] = useState({group: '00', message: ''})
  const [showModal, setShowModal] = useState(false)

  const handleSubmitData = (e) => {
    e.preventDefault()
    axios.post(endpoint, selectValue)
      .then(response => {
        setShowModal(false)
        if(response.data && response.data.status) {
          notify(response.data.message)
        }else {
          notify(response.data.message, 'danger')
        }
      })
      .catch(function () {
        notify('An error occurred', 'danger')
      })
  }

  const handleChange = (e) => {
    setSelectValue({
      ...selectValue,
    [e.target.name]: e.target.value
    })
  }

  const handleElementClick = (e) => {
    e.preventDefault()
    setShowModal(!showModal)
    setSelectValue({group: '00', message: ''})
  }

  return (
    <>
      <span className="flag flag-bookmark-content js-flag-bookmark-content-2 action-flag">
        <a onClick={handleElementClick} title="" href="#" className="ecl-link ecl-link--default ecl-link--icon ecl-link--icon-before ecl-link--standalone ecl-link--flag">
          <span className="ecl-link__icon ecl-icon--s" dangerouslySetInnerHTML={{__html: svg('share', 'ecl-icon ecl-icon--s')}}>
          </span>
          <span className="ecl-link__label">{translations.label || 'Share with another group'}</span>
        </a>
      </span>
      {
        showModal &&
        <ShareModal groups={groups} settings={settings} handleChange={handleChange} handleSubmit={handleSubmitData} setShowModal={setShowModal} selectValue={selectValue} />
      }
    </>
  )
}

export default ShareElement;
