until mysqladmin ping -h "mysql" --silent; do
  >&2 echo "Waiting for database..."
  sleep 3
done
