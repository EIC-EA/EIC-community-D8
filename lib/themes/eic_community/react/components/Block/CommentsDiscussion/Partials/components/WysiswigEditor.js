import React from "react";
import ReactQuill from 'react-quill';
import 'react-quill/dist/quill.snow.css'
import Quill from 'quill'

class WysiswigEditor extends React.Component {
  constructor(props) {
    super(props);
    this.state = {
      text: "",
    }
    this.modules = {
      toolbar: [
        ['bold', 'italic', 'underline','strike'],
        ['link'],
        ['clean']
      ]
    }

    var Link = Quill.import('formats/link');
    var builtInFunc = Link.sanitize;
    Link.sanitize = function customSanitizeLinkInput(linkValueInput) {
        let val = linkValueInput;
    
        // do nothing, since this implies user's already using a custom protocol
        if (val.indexOf("http://") !== 0 && val.indexOf("https://") !== 0) {
          val = "https://" + val;
        }

        return builtInFunc.call(this, val); // retain the built-in logic
    };
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
