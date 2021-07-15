import React from 'react';

class SearchField extends React.Component {
  constructor(props) {
    super(props);

    this.state = {
      searchString: ''
    }

    this.handleChange = this.handleChange.bind(this);
  }

  componentDidMount() {
    const searchString = this.props.searchString;

    this.setState({
      searchString
    })
  }

  handleChange(e) {
    const value = e.target.value;

    this.setState({
      searchString: value
    });

    this.props.searchSolr(value)
  }

  render() {
    return (
      <div className="ecl-filter-sidebar__item">
                          <span className="ecl-filter-sidebar__item-label" tabIndex="-1">
                            {this.props.translations.search_text}
                          </span>

        <div className="ecl-filter-sidebar__item-form" aria-expanded="false">
          <div className="ecl-filter-sidebar__item-fields">
            <div className="ecl-filter-sidebar__item-field">
              <div className="ecl-form-group">
                <input value={this.state.searchString} onChange={e => this.handleChange(e)} className="ecl-text-input ecl-text-input--m" type="text"/>
              </div>
            </div>
          </div>
        </div>
      </div>
    );
  }
}

export default SearchField;
