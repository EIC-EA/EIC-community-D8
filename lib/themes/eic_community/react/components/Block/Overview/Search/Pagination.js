import React from 'react';
import PaginationUi from '@material-ui/lab/Pagination';

class Pagination extends React.Component {
  constructor(props) {
    super(props);

    this.handleChange = this.handleChange.bind(this);
  }

  handleChange(event, value) {
    this.props.changePage(value)
  }

  render() {
    return (
      <PaginationUi count={this.props.total} page={this.props.page} color={"primary"} onChange={this.handleChange} />
    );
  }
}

export default Pagination;
