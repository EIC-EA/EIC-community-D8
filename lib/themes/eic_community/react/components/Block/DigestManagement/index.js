import React, {useState} from 'react';
import axios from "axios";
import Toggle from '../Toggle';
import svg from '../../../svg/svg';

function DigestSettings({toogle, select}) {
  const [toggleStatus, setToggleStatus] = useState(toogle.status);
  const [selectStatus, setSelectStatus] = useState(select.value);

  function handleToggleChange(e) {
    setToggleStatus(!toggleStatus)
    axios.post(toogle.update_url, {value:toggleStatus ? 1 : 0, withCredentials: true});
  }

  function handleSelectChange(e) {
    setSelectStatus(e.target.value)
    axios.post(select.update_url, {value:e.target.value, withCredentials: true});
  }

  return (
    <section className="ecl-my-notifications__section">
      <h2>{ toogle.title }</h2>
      <Toggle
        url={toogle.update_url}
        checked={toggleStatus}
        name={toogle.label}
        toggled={handleToggleChange}
      />
      {toggleStatus && (
        <>
          <h2>{ select.title }</h2>
          <div className="ecl-select__container ecl-select__container--m">
            <select onChange={handleSelectChange} value={selectStatus} className="ecl-select">
              <option value="daily">Daily</option>
              <option value="weekly">Weekly</option>
              <option value="monthly">Monthly</option>
            </select>
            <div className="ecl-select__icon" dangerouslySetInnerHTML={{__html: svg('arrow-down', 'ecl-icon--xs ecl-icon--rotate-180')}}>
            </div>
          </div>
        </>
      )}
    </section>
  )
}

export default DigestSettings;
