import React from 'react';

class CheckboxField extends React.Component {
  constructor(props) {
    super(props);
  }

  render() {
    return (
      <div className="ecl-filter-sidebar__item-field facet-item">
        <fieldset className="ecl-form-group">
          <div className="ecl-checkbox">
            <input type="checkbox" className="ecl-checkbox__input" id="topics-5"/>
            <label htmlFor="topics-5" className="ecl-checkbox__label">
            <span className="ecl-checkbox__box">
            <svg className="ecl-icon ecl-icon--s ecl-checkbox__icon" focusable="false" aria-hidden="true"/>
            </span>
              <span className="facet-item__value">{this.props.value[0]}</span>
              <span className="facet-item__count">{`(${this.props.value[1]})`}</span>
            </label>
          </div>
        </fieldset>
      </div>
    );
  }
}

export default CheckboxField;
