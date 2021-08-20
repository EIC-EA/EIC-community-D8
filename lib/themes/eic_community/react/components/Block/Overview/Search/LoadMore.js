import React from 'react';

class loadMore extends React.Component {
  constructor(props) {
    super(props);
  }

  render() {
    if (Object.keys(this.props.results).length === 0 || (this.props.results.hasOwnProperty('numFound') && this.props.results.numFound === 0))
      return '';

    if (Object.keys(this.props.results.docs).length >= this.props.numFound)
      return '';

    return <a onClick={() => this.props.changePage(this.props.page + 1)}>{this.props.translations.load_more}</a>;
  }
}

export default loadMore;
