import React from "react";
import TextField from '@material-ui/core/TextField';
import Autocomplete from '@material-ui/lab/Autocomplete';
import axios from "axios";

class Search extends React.Component {
  constructor(props) {
    super(props);

    this.state = {
      suggestions: {},
      searchText: ''
    }

    this.onChange = this.onChange.bind(this);
  }

  onChange(e) {
    const value = e.target.value;
    this.setState({
      searchText: value
    })

    this.search();
  }

  search() {
    const self = this;
    const params = {
      search_text: this.state.searchText
    };

    axios.get(this.props.url, {
      params,
      withCredentials: true,
    })
      .then(function (response) {
        console.log(response);
        self.setState({
          suggestions: response.data,
        })
      })
      .catch(function (error) {
        console.log(error);
      })
  }

  render() {
    return <React.Fragment>
      <Autocomplete
        freeSolo
        id="entities-tree--search"
        disableClearable
        options={this.state.searchText ? Object.entries(this.state.suggestions).map((option) => option[1].name) : []}
        renderInput={(params) => (
          <TextField
            {...params}
            label="Search input"
            margin="normal"
            onChange={this.onChange}
            InputProps={{ ...params.InputProps, type: 'search' }}
          />
        )}
      />
    </React.Fragment>;
  }

}

export default Search;
