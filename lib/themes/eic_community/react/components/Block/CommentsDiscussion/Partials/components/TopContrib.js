import React from 'react'
import ReactTooltip from 'react-tooltip';
import svg from '../../../../../svg/svg';

const TopContrib = ({toolTip = true, label}) => {

  return toolTip ?
    <>
      <ReactTooltip backgroundColor={'#004494'} type="info" effect="solid" className='ecl-highlight__tooltip' />
      <span data-tip={label} dangerouslySetInnerHTML={{__html: svg('trophy_circle', 'ecl-icon ecl-icon--s ecl-timestamp__icon')}} />
    </>
    :
    <>
      <span dangerouslySetInnerHTML={{__html: svg('trophy_circle', 'ecl-icon ecl-icon--s ecl-timestamp__icon')}} />
      <span>{label}</span>
    </>

}

export default TopContrib
