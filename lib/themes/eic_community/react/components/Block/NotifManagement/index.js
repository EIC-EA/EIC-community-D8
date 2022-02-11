import React, {useState} from 'react'
import svg from "../../../svg/svg";
import NotifTable from "./components/NotifTable";


const Overview = ({data}) => {
  const [filteredRows, setFilteredRows] = useState(data.items)
  const handleSearchChange = (e) => {
    const value = e.currentTarget.value.toLowerCase()
    if (value.length < 2) {
      setFilteredRows(data.items)
      return
    }
    const items = [...filteredRows].filter(item => item.name.label.toLowerCase().match(value))
    setFilteredRows(value.length ? items : data.items)
  }
  return (
    <>
      <div className="ecl-my-notifications-management__header">
        <h2 className="ecl-notifications-management__title">{data.title}</h2>
        <div className="ecl-subnavigation__searchform-wrapper">
          <form className="ecl-search-form ecl-subnavigation__searchform" role="search">
            <div className="ecl-form-group">
              <input onChange={handleSearchChange} id="input-search" className="ecl-text-input ecl-text-input--m ecl-search-form__text-input"
                     name="search" type="search" placeholder="Search for topics" />
            </div>
            <button className="ecl-button ecl-button--search ecl-search-form__button" type="submit" aria-label="Search">
          <span className="ecl-button__container" dangerouslySetInnerHTML={{__html: `<span class="ecl-button__label" data-ecl-label="true">Search</span> ${svg('search', ' ecl-icon--xs ecl-button__icon ecl-button__icon--after')}`}}>
          </span>
            </button>
          </form>
        </div>
      </div>
      <NotifTable rows={filteredRows} />
    </>
  )
}

export default Overview
