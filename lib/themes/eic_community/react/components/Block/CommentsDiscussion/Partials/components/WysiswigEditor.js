import React from "react";
import ReactQuill from 'react-quill';
import 'react-quill/dist/quill.snow.css'

class WysiswigEditor extends React.Component {
  constructor(props) {
    super(props);
    this.state = {
      text: "",
    }
    this.modules = {
      toolbar: [
        ['bold', 'italic', 'underline','strike'],
        ['clean']
      ],
    }
  }



  render() {
    return (
      <ReactQuill
        {...this.props}
        row={10}
        theme="snow"
        modules={this.modules}
      />

    );
  }
}

export default WysiswigEditor;
