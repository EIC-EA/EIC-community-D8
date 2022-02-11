import React, {useState} from "react";
import svg from "../../../../svg/svg";

const NotifRow = ({row}) => {
  const [showChilds, setShowChilds] = useState(false)
  return (
    <>
      <Row row={row} showChilds={showChilds} setShowChilds={setShowChilds} />
      {showChilds && row?.items?.map((item, index) => <Row row={item} isChild={true} key={index} />)}
    </>
  )
}

const Row = ({row, isChild, setShowChilds, showChilds}) => {
  const [toggle, setToogle] = useState(Boolean(row.state))
  const asChild = !!row.items
  const handleToggleChange =() => {
    setToogle(!toggle)
  }

  return (
    <>
      <tr onClick={() => !isChild ? setShowChilds(!showChilds) : false } className={`ecl-my-notifications-management__table__item ${(!asChild && !isChild) && 'ecl-my-notifications-management__table__item--as-no-child'} ${!isChild && 'ecl-my-notifications-management__table__item--parent'}`}>
        <td className="ecl-my-notifications-management__table__label">
          <div dangerouslySetInnerHTML={
            {__html: `${asChild || isChild ? svg(!isChild ? 'arrow' : 'child-arrow', ' ecl-icon--xs') : ''} <a href=${row.name.path}>${row.name.label}</a>`}
          }/>
        </td>
        <td className="ecl-my-notifications-management__table__status">
          <div>
            <label className="ecl-toogle">
              <input type="checkbox" onChange={handleToggleChange} checked={toggle} name={row.name.label} />
              <div className="ecl-toogle__button"/>
              <span className="ecl-toogle__label"><span>Off</span> <span>On</span></span>
            </label>
          </div>
        </td>
      </tr>
    </>
  )
}

export default React.memo(NotifRow)
