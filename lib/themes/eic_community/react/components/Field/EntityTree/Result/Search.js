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
    this.onChangeAutocomplete = this.onChangeAutocomplete.bind(this);
  }

  onChange(e) {
    const value = e.target.value;
    this.setState({
      searchText: value
    })

    this.search();
  }

  onChangeAutocomplete(e, reason) {
    this.props.addChip(reason[1]);

    this.setState({
      searchText: ''
    })
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
        onChange={this.onChangeAutocomplete}
        options={this.state.searchText ? Object.entries(this.state.suggestions).map((option) => option) : []}
        getOptionLabel={(option) => option[1].name}
        renderInput={(params) => {
          params.inputProps.value = this.state.searchText;
          return <TextField
            {...params}
            label="Search input"
            margin="normal"
            value={this.state.searchText}
            onChange={this.onChange}
          /> }
        }
      />
    </React.Fragment>;
  }

}

export default Search;
