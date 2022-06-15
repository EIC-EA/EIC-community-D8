import React, {useCallback, useEffect, useRef, useState} from 'react';
import CheckboxField from "./CheckboxField";
import svg from "../../../../../svg/svg"

const CollapsedOptions = ({values, facetsValue, bundle, updateFacet, translations}) => {

  const sourceBundle = window.drupalSettings.overview.source_bundle_id;
  const sourceTranslations = window.drupalSettings.translations;
  const minItems = 5
  const [collapsed, setCollapsed] = useState(false)
  const [index, setIndex] = useState(minItems)
  const [isHide, setIsHide] = useState(true)
  const currentGroup = useRef()
  const facets = values.length >= 1 && values[1].filter(option => {
    return (option[0] || option[1] !== 0) && (values[0] !== 'ss_global_content_type' || bundle !== 'library' || ['video', 'document', 'gallery'].includes(option[0]))
  })

  useEffect(() => {
    setIndex(minItems)
    setIsHide(true)
  }, [collapsed])

  const hanldeFilterChecked = useCallback(() => {
    if(window.innerWidth > 768) {
      return
    }
    const el = currentGroup.current
    el.parentElement.scrollIntoView()
  }, [])

  const action = '<span class="ecl-button__label" data-ecl-label="true">'+ (isHide ? translations.show_more : translations.collapse) +'</span>' + svg('arrow-down', `ecl-icon ecl-icon--xs ${isHide && 'ecl-icon--rotate-180'} ecl-button__icon ecl-button__icon--after`)
  const showmoreHandleClick = () => {
    const newIndex = index !== minItems ? minItems : facets.length
    setIndex(newIndex)
    setIsHide(!isHide)
  }
  return (
    <div ref={currentGroup} className="ecl-filter-sidebar__item" aria-expanded={!collapsed}>

      <div onClick={() => setCollapsed(!collapsed)} className="ecl-filter-sidebar__item-label ecl-filter-sidebar__item-label--facets" tabIndex="0">
        {sourceTranslations.sources[sourceBundle].facet[values[0].replace(/[0-9]/g, '')]}
        <div style={{transform: `rotate(${collapsed ? "180" : "0"}deg)`, transition: 'transform .15s ease'}} dangerouslySetInnerHTML={{__html: svg('arrow-down', `ecl-icon ecl-icon--xs`)}} />
      </div>

      <div className="ecl-filter-sidebar__item-form" style={{display: collapsed ? "none" : "block"}} >
        <div className="ecl-filter-sidebar__item-fields">
          {facets.slice(0, index).map((option) => {
            const facetValue = facetsValue[values[0]];
            const checked = facetValue ? facetValue[option[0]] : false;

            return <CheckboxField onChange={hanldeFilterChecked} checked={checked} key={option[0]} facet={values[0]} updateFacet={updateFacet} value={option}/>
          })}
        </div>
        {facets.length > minItems && (
          <div className="ecl-filter-sidebar__item-options">
            <span
                className='ecl-link ecl-link--standalone ecl-link--icon ecl-link--icon-after ecl-activity-stream__call-to-action ecl-link--button ecl-link--button-ghost ecl-link--showmore'
                onClick={showmoreHandleClick}
                dangerouslySetInnerHTML={{__html: action}}
            >
            </span>
          </div>
        )}
      </div>

    </div>
  )
}

export default CollapsedOptions
