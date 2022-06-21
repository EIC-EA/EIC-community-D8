import React, {forwardRef} from 'react';
import DatePicker from "react-datepicker";
import HighlightOffIcon from '@material-ui/icons/HighlightOff';

import "react-datepicker/dist/react-datepicker.css";

class DateField extends React.Component {
  constructor(props) {
    super(props);

    this.handleChange = this.handleChange.bind(this);
    const translations = window.drupalSettings.translations;
  }

  handleChange(e) {
    this.props.updateSearchText(e.target.value);
  }

  render() {
    return (
      <div className="ecl-filter-sidebar__item">
        <span className="ecl-filter-sidebar__item-label" tabIndex="-1">
          {this.props.translations.date_filter_label}
        </span>
        <DatePicker
          selectsRange={true}
          startDate={this.props.dateRange[0]}
          endDate={this.props.dateRange[1]}
          onChange={this.props.updateDateRange}
          onCalendarClose={this.props.handleCalendarClose}
          customInput={<CustomInput haveDate={this.props.dateRange[0]} handleClear={() => this.props.updateDateRange([null, null], true)} />}
        />
      </div>
    );
  }
}

const CustomInput = forwardRef(({ value, onChange, onClick, handleClear, haveDate}) => {

  return (
    <div className="ecl-filter-sidebar__item-form" aria-expanded="false">
      <div className="ecl-filter-sidebar__item-fields">
        <div className="ecl-filter-sidebar__item-field">
          <div className="ecl-form-group react-datepicker_input">
            <input placeholder={"Filter on date"} value={value} onChange={onChange} onClick={onClick} className="ecl-text-input ecl-text-input--m" type="text"/>
            { haveDate && <HighlightOffIcon className={"react-datepicker_clear-btn"} onClick={handleClear} />}
          </div>
        </div>
      </div>
    </div>
  )
})

export default DateField;
