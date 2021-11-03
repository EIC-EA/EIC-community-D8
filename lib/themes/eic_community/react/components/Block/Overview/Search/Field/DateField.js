import React, {forwardRef} from 'react';
import DatePicker from "react-datepicker";

import "react-datepicker/dist/react-datepicker.css";

class DateField extends React.Component {
  constructor(props) {
    super(props);

    this.handleChange = this.handleChange.bind(this);
  }

  handleChange(e) {
    this.props.updateSearchText(e.target.value);
  }

  render() {
    return (
      <div className="ecl-filter-sidebar__item">
        <span className="ecl-filter-sidebar__item-label" tabIndex="-1">
          Dates
        </span>
        <DatePicker
          selectsRange={true}
          startDate={this.props.dateRange[0]}
          endDate={this.props.dateRange[1]}
          onChange={this.props.updateDateRange}
          onCalendarClose={this.props.handleCalendarClose}
          customInput={<CustomInput />}
          isClearable
        />
      </div>
    );
  }
}

const CustomInput = forwardRef(({ value, onChange, onClick }, ref) => {

  return (
    <div className="ecl-filter-sidebar__item-form" aria-expanded="false">
      <div className="ecl-filter-sidebar__item-fields">
        <div className="ecl-filter-sidebar__item-field">
          <div className="ecl-form-group">
            <input value={value} onChange={onChange} onClick={onClick} className="ecl-text-input ecl-text-input--m" type="text"/>
          </div>
        </div>
      </div>
    </div>
  )
})

export default DateField;
