import React from 'react'
import ReactTooltip from 'react-tooltip';
import svg from '../../../../../svg/svg';

const TopContrib = () => {

  return (
    <>
      <ReactTooltip backgroundColor={'#004494'} type="info" effect="solid" className='ecl-highlight__tooltip' />
      <span data-tip={'Top contributor (99k points)'} dangerouslySetInnerHTML={{__html: svg('trophy_circle', 'ecl-icon ecl-icon--s ecl-timestamp__icon')}} />
    </>
  )
}

export default TopContrib
