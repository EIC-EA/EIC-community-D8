import React from 'react';

class SearchField extends React.Component {
  constructor(props) {
    super(props);

    this.state = {
      searchString: ''
    }

    this.timer = null;

    this.handleChange = this.handleChange.bind(this);
    this.search = this.search.bind(this);
  }

  componentDidMount() {
    const searchString = this.props.searchString;

    this.setState({
      searchString
    })
  }

  search() {
    const searchText = this.state.searchString;

    this.props.searchSolr(searchText)
  }

  handleChange(e) {
    const value = e.target.value;

    this.setState({
      searchString: value
    });

    clearTimeout(this.timer);
    this.timer = setTimeout(() => {
      this.search();
    }, 500);
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
