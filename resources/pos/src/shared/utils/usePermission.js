import { useSelector} from "react-redux";

const usePermission = (data) => {
  const {config} = useSelector(state => state)
  const permission = config?.includes(data);
  return permission;
};

export default usePermission;
