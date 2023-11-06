import { SwalParams } from "sweetalert/typings/core";

declare global {
  const swal: (...params: SwalParams) => Promise<any>;
}

// export default swal;
// export as namespace swal;
