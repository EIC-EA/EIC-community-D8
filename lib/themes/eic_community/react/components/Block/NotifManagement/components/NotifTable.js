import React from 'react'
import NotifRow from "./RowNotif";

const NotifTable = ({rows}) => {
  return (
    <table className="ecl-my-notifications-management__table" cellPadding="0" cellSpacing="0">
      <thead>
      <tr>
        <th>Name</th>
        <th>Status</th>
      </tr>
      </thead>
      <tbody>
        {rows.map((row, index) => <NotifRow row={row} key={index} />)}
      </tbody>
    </table>
  )
}

export default NotifTable
