import React from 'react';
import axios from "axios";

class Toggle extends React.Component {
  constructor(props) {
    super(props);

    this.state = {
      toggle: typeof this.props.checked === 'boolean' ? this.props.checked : Boolean(parseInt(this.props.checked)),
      translations: this.props.translations || {
        'toggle_on': 'On',
        'toggle_off': 'Off'
      }
    };
    this.handleToggleChange = this.handleToggleChange.bind(this);
  }
  handleToggleChange() {
    const toggleState = !this.state.toggle;
    this.setState({toggle: toggleState});

    if (this.props.url) {
      axios.post(this.props.url, {state:toggleState, withCredentials: true});
    }
  }
  render() {
    return (
      <label className="ecl-toggle">
        <input type="checkbox" onChange={this.handleToggleChange} checked={this.state.toggle} name={this.props.name} id={this.props.id || this.props.name}/>
        <div className="ecl-toggle__button"/>
        <span className="ecl-toggle__label"><span>{this.state.translations.toggle_off}</span> <span>{this.state.translations.toggle_on}</span></span>
      </label>
    )
  }
}
export default Toggle;
