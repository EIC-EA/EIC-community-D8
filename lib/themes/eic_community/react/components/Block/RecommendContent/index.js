import React, {useEffect, useState} from 'react'
import svg from '../../../svg/svg'
import axios from "axios"
import notify from "../../../Services/NotificationHelper"
import ShareModal from "./components/ShareModal";

const ShareElement = ({label, settings, groupsUrl, treeWidgetSettings, treeWidgetTranslations}) => {

  const [selectValue, setSelectValue] = useState({group: '00', message: ''})
  const [showModal, setShowModal] = useState(false)

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
          <span className="ecl-link__label">{label}</span>
        </a>
      </span>
      {
        showModal &&
        <ShareModal treeWidgetSettings={treeWidgetSettings} treeWidgetTranslations={treeWidgetTranslations} groupsUrl={groupsUrl} settings={settings} setShowModal={setShowModal} />
      }
    </>
  )
}

export default ShareElement;
