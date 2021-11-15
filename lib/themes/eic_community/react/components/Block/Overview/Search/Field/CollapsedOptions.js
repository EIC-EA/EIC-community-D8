import React, {useState} from 'react';
import CheckboxField from "./CheckboxField";
import svg from "../../../../../svg/svg"

const CollapsedOptions = ({values, facetsValue, bundle, updateFacet}) => {

  const sourceBundle = window.drupalSettings.overview.source_bundle_id;
  const translations = window.drupalSettings.translations;
  const [collapsed, setCollapsed] = useState(false)

  return (
    <div className="ecl-filter-sidebar__item" aria-expanded={!collapsed}>

      <div onClick={() => setCollapsed(!collapsed)} className="ecl-filter-sidebar__item-label ecl-filter-sidebar__item-label--facets" tabIndex="0">
        {translations.sources[sourceBundle].facet[values[0]]}
        <div style={{transform: `rotate(${collapsed ? "180" : "0"}deg)`, transition: 'transform .15s ease'}} dangerouslySetInnerHTML={{__html: svg('arrow-down', `ecl-icon ecl-icon--xs`)}} />
      </div>

      <div className="ecl-filter-sidebar__item-form" style={{display: collapsed ? "none" : "block"}} >
        <div className="ecl-filter-sidebar__item-fields">
          {values.length >= 1 && values[1].filter(option => {
            return (option[0] || option[1] !== 0) && (values[0] !== 'ss_global_content_type' || bundle !== 'library' || ['video', 'document', 'gallery'].includes(option[0]))
          }).map((option) => {
            const facetValue = facetsValue[values[0]];
            const checked = facetValue ? facetValue[option[0]] : false;

            return <CheckboxField checked={checked} key={option[0]} facet={values[0]} updateFacet={updateFacet} value={option}/>
          })}
        </div>
      </div>

    </div>
  )
}

export default CollapsedOptions
